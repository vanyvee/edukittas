$transaction = TransactFee::create([
    'school_id' => $user->school_id, // Added
    'student_id' => $user->id,
    'transact_id' => $transactionId,
    'amount_paid' => $request->amount,
    'status' => 'pending',
    'payment_method' => 'opay',
    'expected_fee' => $schoolFee->amount, // Fetched from school_fee
]);

sql

webhook
$transaction = TransactFee::where('transact_id', $request->input('reference'))->first();
$school = School::find($transaction->school_id);
if ($request->input('status') === 'SUCCESS') {
    $transaction->update(['status' => 'success']);
    $school->increment('wallet', $transaction->amount_paid);
}

controller
namespace App\Http\Controllers;

use App\Models\School;
use App\Models\TransactFee;
use App\Models\SchoolFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function initiatePayment(Request $request, SchoolFee $schoolFee)
    {
        $request->validate(['amount' => 'required|numeric|min:100']);
        $user = auth()->user();
        $school = School::find($user->school_id);

        if ($schoolFee->school_id !== $user->school_id) {
            return response()->json(['status' => 'error', 'message' => 'Invalid fee'], 403);
        }

        $transactionId = 'TXN_' . time() . '_' . $user->id;
        $transaction = TransactFee::create([
            'school_id' => $school->id,
            'student_id' => $user->id,
            'transact_id' => $transactionId,
            'amount_paid' => $request->amount,
            'status' => 'pending',
            'payment_method' => 'opay',
            'school_fee_id' => $schoolFee->id,
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . decrypt($school->opay_client_secret), // Assume encrypted
            'MerchantID' => $school->opay_merchant_id,
        ])->post('https://api.opayweb.com/api/v3/payment', [
            'amount' => $request->amount * 100,
            'currency' => 'NGN',
            'reference' => $transactionId,
            'userId' => $user->id,
            'returnUrl' => route('payment.callback', ['schoolFee' => $schoolFee->id]), // Simplified
            'userInfo' => ['userEmail' => $user->email, 'userName' => $user->name],
        ]);

        if ($response->successful()) {
            return response()->json([
                'status' => 'success',
                'payment_url' => $response->json()['data']['payment_url'],
            ]);
        }

        $transaction->update(['status' => 'failed']);
        return response()->json(['status' => 'error', 'message' => 'Payment failed'], 400);
    }

    public function handleWebhook(Request $request)
    {
        $transaction = TransactFee::where('transact_id', $request->input('reference'))->first();
        if (!$transaction) {
            return response()->json(['status' => 'error'], 404);
        }

        $school = School::find($transaction->school_id);
        $signature = $request->header('OPay-Signature');
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, decrypt($school->opay_client_secret));

        if ($signature !== $expectedSignature) {
            return response()->json(['status' => 'error'], 403);
        }

        if ($request->input('status') === 'SUCCESS') {
            $transaction->update(['status' => 'success']);
            $school->increment('wallet', $transaction->amount_paid);
        } else {
            $transaction->update(['status' => 'failed']);
        }

        return response()->json(['status' => 'success']);
    }
}





Route::middleware(['auth:api'])->get('/fees', function () {
    return App\Models\SchoolFee::where('school_id', auth()->user()->school_id)->get();
});




<template>
    <div class="pay-fees">
        <h2>Pay School Fees</h2>
        <form @submit.prevent="initiatePayment">
            <div>
                <label>Select Fee:</label>
                <select v-model="selectedFee" required>
                    <option v-for="fee in fees" :key="fee.id" :value="fee.id">
                        {{ fee.class_name }}: {{ fee.amount }} NGN (Session: {{ fee.session }})
                    </option>
                </select>
            </div>
            <div>
                <label>Amount (NGN):</label>
                <input v-model="amount" type="number" min="100" required />
            </div>
            <button type="submit" :disabled="loading">
                <img src="/images/opay-icon.png" alt="OPay" width="30" /> Pay with OPay
            </button>
        </form>
        <p v-if="error" class="error">{{ error }}</p>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            fees: [],
            selectedFee: null,
            amount: null,
            loading: false,
            error: null,
        };
    },
    mounted() {
        axios.get('/api/fees').then(response => {
            this.fees = response.data;
        }).catch(() => {
            this.error = 'Failed to load fees.';
        });
    },
    methods: {
        async initiatePayment() {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.post(`/payment/initiate/${this.selectedFee}`, {
                    amount: this.amount,
                });
                if (response.data.status === 'success') {
                    window.location.href = response.data.payment_url;
                } else {
                    this.error = response.data.message;
                }
            } catch (error) {
                this.error = 'Payment initiation failed.';
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

use App\Http\Controllers\PaymentController;

Route::middleware(['auth', 'tenant.scope'])->group(function () {
    Route::post('/payment/initiate/{fee}', [PaymentController::class, 'initiatePayment'])->name('payment.initiate');
    Route::get('/payment/callback/{fee}', [PaymentController::class, 'handleCallback'])->name('payment.callback');
});

Route::post('/webhook/opay', [PaymentController::class, 'handleWebhook'])->name('webhook.opay');