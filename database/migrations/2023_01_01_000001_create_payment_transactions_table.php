<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name');
            $table->string('transaction_id');
            $table->string('order_id');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BDT');
            $table->string('status');
            $table->json('payment_details')->nullable();
            $table->json('ipn_response')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->timestamps();
            
            $table->index(['transaction_id']);
            $table->index(['order_id']);
            $table->index(['customer_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_transactions');
    }
}