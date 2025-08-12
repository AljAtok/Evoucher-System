<?php

class Coupon extends RabbitORM\Model {
	// protected $table = "coupon_tbl";

	const CouponDefinition = '{"name": "Coupons", "table": "coupon_tbl"}';
    private $couponId; 
    const userIdDefinition = '{"name":"coupon_id", "column":"coupon_id", "primaryKey":"true"}';
    private $name; 
    const nameDefinition = '{"name":"coupon_name","column":"coupon_name"}';
    // private $login; 
    // const loginDefinition = '{"name":"login","column":"client_login"}';
}
