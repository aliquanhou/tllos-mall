<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("merchants", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id")->comment("关联用户ID");
            $table->string("name", 100)->unique()->comment("店铺名称");
            $table->string("logo", 255)->nullable();
            $table->string("banner", 255)->nullable();
            $table->text("description")->nullable();
            $table->unsignedBigInteger("category_id")->comment("经营类目");
            $table->string("contact_name", 50);
            $table->string("contact_mobile", 20);
            $table->string("contact_email", 100)->nullable();
            $table->string("company_name", 100)->nullable()->comment("公司名称");
            $table->string("business_license", 255)->nullable()->comment("营业执照");
            $table->string("legal_person", 50)->nullable();
            $table->string("id_card", 30)->nullable();
            $table->string("id_card_front", 255)->nullable();
            $table->string("id_card_back", 255)->nullable();
            $table->unsignedBigInteger("province_id");
            $table->unsignedBigInteger("city_id");
            $table->unsignedBigInteger("district_id");
            $table->string("address", 255);
            $table->decimal("balance", 12, 2)->default(0)->comment("可用余额");
            $table->decimal("frozen", 12, 2)->default(0)->comment("冻结金额");
            $table->decimal("total_income", 12, 2)->default(0);
            $table->decimal("total_settlement", 12, 2)->default(0);
            $table->integer("product_count")->default(0);
            $table->integer("order_count")->default(0);
            $table->decimal("rating", 3, 2)->default(5.00)->comment("店铺评分");
            $table->tinyInteger("level")->default(1)->comment("店铺等级");
            $table->tinyInteger("status")->default(0)->comment("0待审核 1正常 2禁用 3审核拒绝");
            $table->string("reject_reason", 255)->nullable();
            $table->timestamp("approved_at")->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index("user_id");
            $table->index("status");
        });
    }
    public function down(): void { Schema::dropIfExists("merchants"); }
};
