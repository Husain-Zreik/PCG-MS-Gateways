<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class CheckoutController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $products = $request->input('products', []);
        $companyName = $request->input('company-name');
        $shippingCost = $request->input('shipping-cost', null);
        $sessionMetadata = $request->input('metadata', []); // Session-level metadata
        $cancelUrl = $request->input('cancel_url', null); // Optional cancel URL

        if (empty($companyName)) {
            return response()->json(['error' => 'Company name is required.'], 400);
        }

        if (empty($products)) {
            return response()->json(['error' => 'No products provided.'], 400);
        }

        $lineItems = [];
        $totalAmount = 0;

        foreach ($products as $product) {
            if (!isset($product['name'], $product['price'], $product['quantity'])) {
                return response()->json(['error' => 'Invalid product data.'], 400);
            }

            $productData = [
                'name' => $product['name'],
                'description' => $product['description'] ?? null,
                'images' => $product['images'] ?? null,
                'metadata' => $product['metadata'] ?? [],
            ];

            $productPriceInCents = round($product['price'] * 100);
            $productTotal = $productPriceInCents * $product['quantity'];
            $totalAmount += $productTotal;

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => $productData,
                    'unit_amount' => $productPriceInCents,
                ],
                'quantity' => $product['quantity'],
            ];
        }

        $stripeFeePercentage = 2.9 / 100;
        $stripeFeeFixed = 30;
        $stripeFeePercentageAmount = round($totalAmount * $stripeFeePercentage);
        $totalFee = $stripeFeePercentageAmount + $stripeFeeFixed;

        $lineItems[] = [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Stripe Fee',
                    'description' => 'Standard Stripe processing fee: 2.9% + 30¢',
                    'images' => ['https://images.stripeassets.com/fzn2n1nzq965/HTTOloNPhisV9P4hlMPNA/cacf1bb88b9fc492dfad34378d844280/Stripe_icon_-_square.svg?q=80&w=1082'],
                ],
                'unit_amount' => $totalFee,
            ],
            'quantity' => 1,
        ];

        $allowedCountries = [
            "AC",
            "AD",
            "AE",
            "AF",
            "AG",
            "AI",
            "AL",
            "AM",
            "AO",
            "AQ",
            "AR",
            "AT",
            "AU",
            "AW",
            "AX",
            "AZ",
            "BA",
            "BB",
            "BD",
            "BE",
            "BF",
            "BG",
            "BH",
            "BI",
            "BJ",
            "BL",
            "BM",
            "BN",
            "BO",
            "BQ",
            "BR",
            "BS",
            "BT",
            "BV",
            "BW",
            "BY",
            "BZ",
            "CA",
            "CD",
            "CF",
            "CG",
            "CH",
            "CI",
            "CK",
            "CL",
            "CM",
            "CN",
            "CO",
            "CR",
            "CV",
            "CW",
            "CY",
            "CZ",
            "DE",
            "DJ",
            "DK",
            "DM",
            "DO",
            "DZ",
            "EC",
            "EE",
            "EG",
            "EH",
            "ER",
            "ES",
            "ET",
            "FI",
            "FJ",
            "FK",
            "FO",
            "FR",
            "GA",
            "GB",
            "GD",
            "GE",
            "GF",
            "GG",
            "GH",
            "GI",
            "GL",
            "GM",
            "GN",
            "GP",
            "GQ",
            "GR",
            "GS",
            "GT",
            "GU",
            "GW",
            "GY",
            "HK",
            "HN",
            "HR",
            "HT",
            "HU",
            "ID",
            "IE",
            "IL",
            "IM",
            "IN",
            "IO",
            "IQ",
            "IT",
            "JE",
            "JM",
            "JO",
            "JP",
            "KE",
            "KG",
            "KH",
            "KI",
            "KM",
            "KN",
            "KR",
            "KW",
            "KY",
            "KZ",
            "LA",
            "LB",
            "LC",
            "LI",
            "LK",
            "LR",
            "LS",
            "LT",
            "LU",
            "LV",
            "LY",
            "MA",
            "MC",
            "MD",
            "ME",
            "MF",
            "MG",
            "MK",
            "ML",
            "MM",
            "MN",
            "MO",
            "MQ",
            "MR",
            "MS",
            "MT",
            "MU",
            "MV",
            "MW",
            "MX",
            "MY",
            "MZ",
            "NA",
            "NC",
            "NE",
            "NG",
            "NI",
            "NL",
            "NO",
            "NP",
            "NR",
            "NU",
            "NZ",
            "OM",
            "PA",
            "PE",
            "PF",
            "PG",
            "PH",
            "PK",
            "PL",
            "PM",
            "PN",
            "PR",
            "PS",
            "PT",
            "PY",
            "QA",
            "RE",
            "RO",
            "RS",
            "RU",
            "RW",
            "SA",
            "SB",
            "SC",
            "SE",
            "SG",
            "SH",
            "SI",
            "SJ",
            "SK",
            "SL",
            "SM",
            "SN",
            "SO",
            "SR",
            "SS",
            "ST",
            "SV",
            "SX",
            "SZ",
            "TA",
            "TC",
            "TD",
            "TF",
            "TG",
            "TH",
            "TJ",
            "TK",
            "TL",
            "TM",
            "TN",
            "TO",
            "TR",
            "TT",
            "TV",
            "TW",
            "TZ",
            "UA",
            "UG",
            "US",
            "UY",
            "UZ",
            "VA",
            "VC",
            "VE",
            "VG",
            "VN",
            "VU",
            "WF",
            "WS",
            "XK",
            "YE",
            "YT",
            "ZA",
            "ZM",
            "ZW",
            "ZZ",
        ];

        $shippingOptions = [];
        if ($shippingCost !== null) {
            $shippingOptions[] = [
                'shipping_rate_data' => [
                    'type' => 'fixed_amount',
                    'fixed_amount' => [
                        'amount' => $shippingCost * 100,
                        'currency' => 'usd',
                    ],
                    'display_name' => 'Standard Shipping',
                ],
            ];
        }

        try {
            // Create Stripe Checkout session
            $checkoutSessionData = [
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => url('api/checkout/success?session_id={CHECKOUT_SESSION_ID}'),
                'billing_address_collection' => 'required',
                'shipping_address_collection' => [
                    'allowed_countries' => $allowedCountries,
                ],
                'invoice_creation' => [
                    'enabled' => true,
                    'invoice_data' => [
                        'description' => 'Thank you for choosing our services.',
                        'footer' => $companyName . ' © ' . date('Y') . '. All rights reserved.',
                        'custom_fields' => [
                            ['name' => 'Company', 'value' => $companyName],
                        ],
                    ],
                ],
                'shipping_options' => $shippingOptions,
                'custom_text' => [
                    'shipping_address' => [
                        'message' => 'Please ensure your shipping details are accurate. We cannot modify orders after submission.',
                    ],
                    'after_submit' => [
                        'message' => 'By completing your purchase, you confirm your order and agree to the purchase conditions.',
                    ],
                ],
                'metadata' => $sessionMetadata,
            ];

            // Only add cancel_url if it's provided
            if ($cancelUrl) {
                $checkoutSessionData['cancel_url'] = $cancelUrl;
            }

            $checkoutSession = StripeSession::create($checkoutSessionData);

            return response()->json(['url' => $checkoutSession->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function success(Request $request)
    {
        $sessionId = $request->query('session_id'); // Retrieve the session_id from the URL

        if (!$sessionId) {
            return response()->json(['error' => 'Session ID is missing.'], 400);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status === 'paid') {

                $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);

                $invoice = \Stripe\Invoice::retrieve($paymentIntent->invoice);

                $invoiceUrl = $invoice['hosted_invoice_url'];

                // Retrieve the invoice PDF (Stripe provides a link to download the invoice as PDF)
                $invoicePdfUrl = $invoice->invoice_pdf;  // URL to the PDF version of the invoice

                $customer = \Stripe\Customer::retrieve($paymentIntent->customer);

                // Extract relevant customer details
                $customerDetails = [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'shipping_address' => $customer->shipping,
                    'billing_address' => $customer->address
                ];


                $invoiceMetadata = $session->metadata;  // Metadata related to the invoice

                // return response()->json([
                //     // 'session' => $session,
                //     'invoice_url' => $invoiceUrl,
                //     'invoice_pdf_url' => $invoicePdfUrl,
                //     'customer_details' => $customerDetails,
                //     'metadata' => $invoiceMetadata
                // ]);

                return redirect($invoiceUrl);
            } else {
                return response()->json(['error' => 'Payment not successful'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve checkout session: ' . $e->getMessage()], 500);
        }
    }
    // Cancel method - to handle the checkout cancellation
    public function cancel()
    {
        return view('checkout.cancel');
    }
}
