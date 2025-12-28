<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FawaterakService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('fawaterak.api_key');
        $this->baseUrl = config('fawaterak.base_url');
    }

    /**
     * إنشاء فاتورة جديدة
     */
    public function createInvoice(Order $order): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/createInvoice', [
                'payment_method_id' => 1, // جميع طرق الدفع
                'cartTotal' => $order->price,
                'currency' => 'EGP',
                'customer' => [
                    'first_name' => $order->asker->name,
                    'last_name' => '',
                    'email' => $order->asker->email ?? 'no-email@asalny.com',
                    'phone' => $order->asker->phone,
                    'address' => 'N/A',
                ],
                'redirectionUrls' => [
                    'successUrl' => config('fawaterak.success_url') . '?order_id=' . $order->id,
                    'failUrl' => config('fawaterak.failure_url') . '?order_id=' . $order->id,
                    'pendingUrl' => config('fawaterak.failure_url') . '?order_id=' . $order->id,
                ],
                'cartItems' => [[
                    'name' => 'إجابة السؤال #' . $order->question_id,
                    'price' => $order->price,
                    'quantity' => 1,
                ]],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('✅ تم إنشاء فاتورة Fawaterak', [
                    'order_id' => $order->id,
                    'invoice_id' => $data['data']['invoice_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'data' => $data['data'],
                ];
            }

            Log::error('❌ فشل إنشاء فاتورة Fawaterak', [
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'فشل إنشاء الفاتورة',
            ];

        } catch (Exception $e) {
            Log::error('❌ خطأ في إنشاء فاتورة Fawaterak', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * التحقق من حالة الفاتورة
     */
    public function getInvoiceStatus(string $invoiceId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/getInvoiceData/' . $invoiceId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'فشل الحصول على حالة الفاتورة',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * إنشاء فاتورة إيداع للمحفظة
     */
    public function createDepositInvoice($user, float $amount): array
    {
        // 🧪 TEST MODE - إرجاع بيانات وهمية
        $testMode = config('fawaterak.test_mode', true);
        
        if ($testMode) {
            Log::info('🧪 TEST MODE: إنشاء فاتورة وهمية', [
                'user_id' => $user->id,
                'amount' => $amount,
            ]);
            
            $fakeInvoiceId = 'TEST_INV_' . time() . '_' . $user->id;
            
            return [
                'success' => true,
                'data' => [
                    'invoice_id' => $fakeInvoiceId,
                    'invoice_key' => 'test_key_' . uniqid(),
                    'url' => route('fawaterak.test.payment', [
                        'invoice' => $fakeInvoiceId,
                        'amount' => $amount,
                        'user_id' => $user->id
                    ]),
                    'amount' => $amount,
                ],
            ];
        }

        try {
            Log::info('🔄 محاولة إنشاء فاتورة Fawaterak', [
                'user_id' => $user->id,
                'amount' => $amount,
                'api_key_exists' => !empty($this->apiKey),
                'base_url' => $this->baseUrl,
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/createInvoice', [
                    'payment_method_id' => 1,
                    'cartTotal' => $amount,
                    'currency' => 'EGP',
                    'customer' => [
                        'first_name' => $user->name,
                        'last_name' => '',
                        'email' => $user->email ?? 'no-email@asalny.com',
                        'phone' => $user->phone,
                        'address' => 'N/A',
                    ],
                    'redirectionUrls' => [
                        'successUrl' => config('fawaterak.success_url') . '?type=deposit&user_id=' . $user->id,
                        'failUrl' => config('fawaterak.failure_url') . '?type=deposit',
                        'pendingUrl' => config('fawaterak.failure_url') . '?type=deposit',
                    ],
                    'cartItems' => [[
                        'name' => 'إيداع في المحفظة',
                        'price' => $amount,
                        'quantity' => 1,
                    ]],
                ]);

            Log::info('📥 استجابة Fawaterak', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!isset($data['data'])) {
                    Log::error('❌ استجابة Fawaterak غير متوقعة', [
                        'response_data' => $data,
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => 'استجابة غير متوقعة من بوابة الدفع',
                    ];
                }
                
                Log::info('✅ تم إنشاء فاتورة إيداع Fawaterak', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'invoice_id' => $data['data']['invoice_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'data' => $data['data'],
                ];
            }

            $errorData = $response->json();
            Log::error('❌ فشل إنشاء فاتورة إيداع Fawaterak', [
                'status' => $response->status(),
                'response' => $errorData,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $errorData['message'] ?? 'فشل إنشاء الفاتورة',
                'error_details' => $errorData,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('❌ خطأ في الاتصال مع Fawaterak', [
                'error' => $e->getMessage(),
                'base_url' => $this->baseUrl,
            ]);

            return [
                'success' => false,
                'message' => 'فشل الاتصال مع بوابة الدفع. يرجى المحاولة لاحقاً',
            ];
        } catch (Exception $e) {
            Log::error('❌ خطأ في إنشاء فاتورة إيداع Fawaterak', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
