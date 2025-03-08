<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Payout;
use Stripe\Balance;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Checkout\Session;
use Stripe\Token;
class PaymentController extends Controller
{

    public function index()
    {
        return view('payment');
    }

        public function charge(Request $request)
    {
       
       try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => 5000,
                'currency' => 'usd',
                'payment_method' => $request->payment_method,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => url('/payment-success'),
            ]);
           
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
        
    }

    public function pay(Request $request){

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create a PaymentMethod
            $paymentMethod = PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'number'    => $request->get('card_no'),
                    'exp_month' => $request->get('ccExpiryMonth'),
                    'exp_year'  => $request->get('ccExpiryYear'),
                    'cvc'       => $request->get('cvvNumber'),
                ],
            ]);

            // Create a PaymentIntent
            $paymentIntent = PaymentIntent::create([
                'amount' => 2049, // Amount in cents (e.g., $20.49)
                'currency' => 'usd',
                'payment_method' => $paymentMethod->id,
                'confirm' => true, // Confirm immediately
            ]);

            return redirect()->back()->with('success', 'Payment successful!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        
    }

    public function checkout()
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Test Product',
                    ],
                    'unit_amount' => 5000,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => url('/success'),
            'cancel_url' => url('/cancel'),
        ]);

        return redirect($session->url);
    }

    public function createPayout(Request $request)
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $payout = Payout::create([
                'amount' => $request->amount * 100,
                'currency' => 'usd',
                'method' => 'standard',
            ]);
            return response()->json(['success' => true, 'payout' => $payout]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function checkBalance()
{
    try {
        Stripe::setApiKey(config('services.stripe.secret'));

        $balance = Balance::retrieve();
        return response()->json(['balance' => $balance]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function my(){




    return view('pay');
}
}