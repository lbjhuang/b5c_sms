<?php
/**
 * User: TR
 * Date: 19/04/26
 * Time: 10:05
 */

class FinanceMapService extends Service
{
	public function rulesEbayAuMap()
	{
		$mapping = [
		    'order_no' => ['row'=>'A', 'adjustMap' => ['unique', 'filter'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'order_created_date' => ['row'=>'V', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'paid_on_date' => ['row'=>'X', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'start_date' => ['row'=>'X', 'adjustMap' => ['filter','UTC'], 'mathMap' => [], 'validMap' => []],
		    'deposit_date' => ['row'=>'X', 'adjustMap' => ['filter','UTC'], 'mathMap' => [], 'validMap' => []],
		    'amount' => ['row'=>'T', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'payment_method' => ['row'=>'U', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => []],
		    'goods_name' => ['row'=>'M', 'adjustMap' => ['N'=>'goods'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'sku' => ['row'=>'N', 'adjustMap' => ['sku'], 'mathMap' => [], 'validMap' => []],
		    'plat_goods_id' => ['row'=>'L', 'adjustMap' => ['filter','E'], 'mathMap' => [], 'validMap' => []],
		    'goods_number' => ['row'=>'O', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => []],
		    'sale_amount' => ['row'=>'P', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'shared_amount' => ['row'=>'P', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'buyer_amount' => ['row'=>'P', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'our_collection_of_cost' => ['row'=>'R', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'shipped_date' => ['row'=>'Y', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
			'our_collection_buyer_cost_charge' => ['row'=>'S', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'plat_service_cost' => ['row'=>'Q', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
		];
		return $mapping;
	}

	public function rulesEbayAu3Map()
	{
		$mapping = [
		    'order_no' => ['row'=>'A', 'adjustMap' => ['unique', 'filter'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'order_created_date' => ['row'=>'Y', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'paid_on_date' => ['row'=>'AA', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'start_date' => ['row'=>'AA', 'adjustMap' => ['filter','UTC'], 'mathMap' => [], 'validMap' => []],
		    'deposit_date' => ['row'=>'AA', 'adjustMap' => ['filter','UTC'], 'mathMap' => [], 'validMap' => []],
		    'amount' => ['row'=>'V', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'payment_method' => ['row'=>'W', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => []],
		    'goods_name' => ['row'=>'O', 'adjustMap' => ['AG'=>'goods'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'sku' => ['row'=>'AG', 'adjustMap' => ['sku'], 'mathMap' => [], 'validMap' => []],
		    'plat_goods_id' => ['row'=>'M', 'adjustMap' => ['filter','E'], 'mathMap' => [], 'validMap' => []],
		    'goods_number' => ['row'=>'P', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => []],
		    'sale_amount' => ['row'=>'Q', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'shared_amount' => ['row'=>'Q', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'buyer_amount' => ['row'=>'Q', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'buyer_amount_tax_1' => ['row'=>'S', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'buyer_amount_tax_2' => ['row'=>'U', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			//'palat_collection_buyer_amount_tax_1' => ['row'=>'S', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			// 'palat_collection_buyer_amount_tax_2' => ['row'=>'U', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],


			'our_collection_of_cost' => ['row'=>'T', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'shipped_date' => ['row'=>'AB', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
			'plat_service_cost' => ['row'=>'R', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
		];
		return $mapping;
	}

	public function rulesEbayUkMap()
	{
		$mapping = [
		    'order_no' => ['row'=>'A', 'adjustMap' => ['unique', 'filter'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'order_created_date' => ['row'=>'W', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'paid_on_date' => ['row'=>'Y', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'start_date' => ['row'=>'Y', 'adjustMap' => ['filter','UTC'], 'mathMap' => [], 'validMap' => []],
		    'deposit_date' => ['row'=>'Y', 'adjustMap' => ['filter','UTC'], 'mathMap' => [], 'validMap' => []],
		    'amount' => ['row'=>'U', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'payment_method' => ['row'=>'V', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => []],
		    'goods_name' => ['row'=>'M', 'adjustMap' => ['N'=>'goods'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'sku' => ['row'=>'N', 'adjustMap' => ['sku'], 'mathMap' => [], 'validMap' => []],
		    'plat_goods_id' => ['row'=>'L', 'adjustMap' => ['filter','E'], 'mathMap' => [], 'validMap' => []],
		    'goods_number' => ['row'=>'O', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => []],
		    'sale_amount' => ['row'=>'P', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'shared_amount' => ['row'=>'T', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'buyer_amount' => ['row'=>'T', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'our_collection_of_cost' => ['row'=>'S', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'shipped_date' => ['row'=>'Z', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
			'our_collection_buyer_cost_charge' => ['row'=>'T', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'plat_service_cost' => ['row'=>'R', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
		];
		return $mapping;
	}

	public function rulesEbayUSMap()
	{
		$mapping = [
		    'order_no' => ['row'=>'A', 'adjustMap' => ['unique', 'filter'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'order_created_date' => ['row'=>'W', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'paid_on_date' => ['row'=>'Y', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'start_date' => ['row'=>'Y', 'adjustMap' => ['filter','UTC'], 'mathMap' => [], 'validMap' => []],
		    'deposit_date' => ['row'=>'Y', 'adjustMap' => ['filter','UTC'], 'mathMap' => [], 'validMap' => []],
		    'amount' => ['row'=>'U', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'payment_method' => ['row'=>'V', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => []],
		    'goods_name' => ['row'=>'M', 'adjustMap' => ['N'=>'goods'], 'mathMap' => ['firstline'], 'validMap' => []],
		    'sku' => ['row'=>'N', 'adjustMap' => ['sku'], 'mathMap' => [], 'validMap' => []],
		    'plat_goods_id' => ['row'=>'L', 'adjustMap' => ['filter','E'], 'mathMap' => [], 'validMap' => []],
		    'goods_number' => ['row'=>'O', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => []],
		    'sale_amount' => ['row'=>'P', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'shared_amount' => ['row'=>'P', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'buyer_amount' => ['row'=>'P', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'buyer_amount_tax' => ['row'=>'R', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'palat_collection_buyer_amount_tax' => ['row'=>'R', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],

			'our_collection_of_cost' => ['row'=>'S', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'shipped_date' => ['row'=>'Z', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],
			'our_collection_buyer_cost_charge' => ['row'=>'T', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
			'plat_service_cost' => ['row'=>'Q', 'adjustMap' => ['$'], 'mathMap' => ['firstline'], 'validMap' => []],
		];
		return $mapping;
	}

	public function rulesShopeeMyMap()
	{
		$mapping = [
		    'order_no' => ['row'=>'', 'adjustMap' => ['unique', 'filter'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],

		    'order_created_date' => ['row'=>'I', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'paid_on_date' => ['row'=>'J', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'start_date' => ['row'=>'AW', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'amount' => ['row'=>'AH', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['J' => 'timeFormat']],
		    
		    

		    'goods_name' => ['row'=>'L', 'adjustMap' => ['K'=>'goods'], 'mathMap' => [], 'validMap' => ['J' => 'timeFormat']],
		    'sku' => ['row'=>'K', 'adjustMap' => ['sku'], 'mathMap' => [], 'validMap' => ['J' => 'timeFormat']],




		    'goods_number' => ['row'=>'Q', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['J' => 'timeFormat']],

		    'sale_amount' => ['row'=>'Q*O', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['J' => 'timeFormat']],
		    'our_discount_amount' => ['row'=>'T', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['J' => 'timeFormat']],
		    'our_coupon_amount' => ['row'=>'Z', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'our_bind_sale_amount' => ['row'=>'AE', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'shared_amount' => ['row'=>'R', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['J' => 'timeFormat']],
		    'plat_coupon_amount' => ['row'=>'AB', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'plat_integral_amount' => ['row'=>'AF', 'adjustMap' => ['/@100'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'plat_bind_sale_amount' => ['row'=>'AD', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'credit_card_dealer_amount' => ['row'=>'AG', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],


		    'shipped_date' => ['row'=>'H', 'adjustMap' => ['filter'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'confirmed_date' => ['row'=>'AW', 'adjustMap' => ['filter'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'buyer_freight' => ['row'=>'AI', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],

		];
		return $mapping;
	}

	public function rulesShopeeId()
	{
		$mapping = [
		    'order_no' => ['row'=>'', 'adjustMap' => ['unique', 'filter'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],

		    'order_created_date' => ['row'=>'I', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'paid_on_date' => ['row'=>'J', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'start_date' => ['row'=>'AQ', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],


		    'amount_1' => ['row'=>'R', 'adjustMap' => ['$-'], 'mathMap' => ['+'], 'validMap' => ['J' => 'timeFormat']],
		    'amount_2' => ['row'=>'AF', 'adjustMap' => ['$-'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    
		    

		    'goods_name' => ['row'=>'L', 'adjustMap' => ['K'=>'goods'], 'mathMap' => [], 'validMap' => ['J' => 'timeFormat']],
		    'sku' => ['row'=>'K', 'adjustMap' => ['sku'], 'mathMap' => [], 'validMap' => ['J' => 'timeFormat']],




		    'goods_number' => ['row'=>'Q', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['J' => 'timeFormat']],

		    'sale_amount' => ['row'=>'Q*O', 'adjustMap' => ['$-'], 'mathMap' => ['+'], 'validMap' => ['J' => 'timeFormat']],
		    'our_discount_amount' => ['row'=>'T', 'adjustMap' => ['$-'], 'mathMap' => ['+'], 'validMap' => ['J' => 'timeFormat']],
		    'our_coupon_amount' => ['row'=>'Y', 'adjustMap' => ['$-'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'our_bind_sale_amount' => ['row'=>'AC', 'adjustMap' => ['$-'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'shared_amount' => ['row'=>'R', 'adjustMap' => ['$-'], 'mathMap' => ['+'], 'validMap' => ['J' => 'timeFormat']],
		    'plat_coupon_amount' => ['row'=>'Z', 'adjustMap' => ['$-'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'plat_integral_amount' => ['row'=>'AD', 'adjustMap' => ['$-','/@100'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'plat_bind_sale_amount' => ['row'=>'AB', 'adjustMap' => ['$-'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'credit_card_dealer_amount' => ['row'=>'AE', 'adjustMap' => ['$-'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],


		    'shipped_date' => ['row'=>'H', 'adjustMap' => ['filter'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'confirmed_date' => ['row'=>'AQ', 'adjustMap' => ['filter'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],
		    'buyer_freight' => ['row'=>'AF', 'adjustMap' => ['$-'], 'mathMap' => ['firstline'], 'validMap' => ['J' => 'timeFormat']],

		];
		return $mapping;
	}

	public function rulesShopeeThMap()
	{
		$mapping = [
		    'order_no' => ['row'=>'', 'adjustMap' => ['unique', 'filter'], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],

		    'order_created_date' => ['row'=>'E', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'paid_on_date' => ['row'=>'F', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'start_date' => ['row'=>'AS', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'amount_1' => ['row'=>'AH', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'amount_2' => ['row'=>'AD', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'amount_3' => ['row'=>'AE', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    
		    

		    'goods_name' => ['row'=>'N', 'adjustMap' => ['M'=>'goods'], 'mathMap' => [], 'validMap' => ['F' => 'timeFormat']],
		    'sku' => ['row'=>'M', 'adjustMap' => ['sku'], 'mathMap' => [], 'validMap' => ['F' => 'timeFormat']],




		    'goods_number' => ['row'=>'S', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['F' => 'timeFormat']],

		    'sale_amount' => ['row'=>'Q*S', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['F' => 'timeFormat']],
		    // 'our_discount_amount' => ['row'=>'T', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['F' => 'timeFormat']],
		    'our_coupon_amount' => ['row'=>'V', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'our_bind_sale_amount' => ['row'=>'Z', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'shared_amount_1' => ['row'=>'AH', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'shared_amount_2' => ['row'=>'AG', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'shared_amount_3' => ['row'=>'AD', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'shared_amount_4' => ['row'=>'AE', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],

		    'plat_coupon_amount' => ['row'=>'W', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'plat_integral_amount' => ['row'=>'AB', 'adjustMap' => ['/@100'], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'plat_bind_sale_amount' => ['row'=>'AA', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'credit_card_dealer_amount' => ['row'=>'AC', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],

		    'commission_1' => ['row'=>'AD', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'commission_2' => ['row'=>'AE', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],


		    


		    'shipped_date' => ['row'=>'L', 'adjustMap' => ['filter'], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'confirmed_date' => ['row'=>'AS', 'adjustMap' => ['filter'], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],
		    'buyer_freight' => ['row'=>'AG', 'adjustMap' => [], 'mathMap' => ['firstline'], 'validMap' => ['F' => 'timeFormat']],

		];
		return $mapping;
	}

	public function rulesLazadaMap()
	{
		$mapping = [
			'order_no' => ['row'=>'', 'adjustMap' => ['unique', 'filter'], 'mathMap' => ['firstline'], 'validMap' => []],	

			// info
			'paid_on_date' => ['row'=>'A', 'adjustMap' => ['filter','UTC'], 'mathMap' => ['firstline'], 'validMap' => []],	
			'start_date' => ['row'=>'L', 'adjustMap' => ['T-L', 'UTC'], 'mathMap' => [], 'validMap' => []],
			'end_date' => ['row'=>'L', 'adjustMap' => ['T-R', 'UTC'], 'mathMap' => [], 'validMap' => []],
			'deposit_date' => ['row'=>'L', 'adjustMap' => ['T-R', 'UTC', 'week'], 'mathMap' => [], 'validMap' => []],	
			'amount' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => []],	

			// goods
			'goods_name' => ['row'=>'E', 'adjustMap' => ['F'=>'goods'], 'mathMap' => [], 'validMap' => ['B' => 'Orders-Item Charges', 'C' => 'Item Price Credit']],	
			'sku' => ['row'=>'F', 'adjustMap' => ['sku'], 'mathMap' => [], 'validMap' => ['B' => 'Orders-Item Charges', 'C' => 'Item Price Credit']],
			'plat_goods_id' => ['row'=>'O', 'adjustMap' => ['E'], 'mathMap' => ['firstline'], 'validMap' => []],	


					
			// sell
			'sale_amount' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['B' => 'Orders-Item Charges', 'C' => 'Item Price Credit']],	
			'our_coupon_amount' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+', '||'], 'validMap' => ['B' => 'Orders-Item Charges', 'C' => 'Promotional Charges Vouchers']],	
			'our_bind_sale_amount' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+', '||'], 'validMap' => ['B' => 'Orders-Item Charges', 'C' => 'Promotional Charges Bundles']],	

			'shared_amount_1' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['B' => 'Orders-Item Charges', 'C' => 'Item Price Credit']],	
			'shared_amount_2' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['B' => 'Orders-Item Charges', 'C' => 'Promotional Charges Vouchers']],	
			'shared_amount_3' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['B' => 'Orders-Item Charges', 'C' => 'Promotional Charges Bundles']],	

			'commission_1' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+', '||'], 'validMap' => ['B' => 'Orders-Lazada Fees', 'C' => 'Commission']],	
			'commission_2' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+', '||'], 'validMap' => ['B' => 'Orders-Lazada Fees', 'C' => 'Payment Fee']],	

			// ship
			'buyer_freight' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['B' => 'Orders-Other Credit', 'C' => 'Shipping Fee (Paid By Customer)']],	

			'our_collection_buyer_freight_tax' => ['row'=>'I', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['B' => 'Orders-Lazada Fees', 'C' => 'Shipping Fee (Charged by Lazada)']],	
			'our_payment_buyer_freight_tax' => ['row'=>'I', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['B' => 'Orders-Lazada Fees', 'C' => 'Shipping Fee (Charged by Lazada)']],	
			'plat_service_cost' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+', '||'], 'validMap' => ['B' => 'Orders-Lazada Fees', 'C' => 'Shipping Fee (Charged by Lazada)']],	
			'distribution_cost' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+', '||'], 'validMap' => ['B' => '3P Services-Shipping', 'C' => 'Shipping Fee (Charged By 3P)']],	


			// return
'refund_date' => ['row'=>'A', 'adjustMap' => [], 'mathMap' => ['mintime'], 'validMap' => ['B' => 'Refunds-Item Charges', 'C' => 'Reversal Item Price']],	

			'our_coupon_amount_return' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['B' => 'Refunds-Item Charges', 'C' => 'Reversal Promotional Charges Vouchers']],	
			'our_bind_sale_amount_return' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['B' => 'Refunds-Item Charges', 'C' => 'Reversal Promotional Charges Bundles']],	
			'buyer_amount_return' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+', '||'], 'validMap' => ['B' => 'Refunds-Item Charges', 'C' => 'Reversal Item Price']],	

			'commission_return' => ['row'=>'H', 'adjustMap' => [], 'mathMap' => ['+'], 'validMap' => ['B' => 'Refunds-Lazada Fees', 'C' => 'Reversal Commission']],	
		];
		return $mapping;
	}

	public function test()
	{
		$mapping = [
/*
			// info
			'order_no' => ['row'=>'', 'adjustMap' => ['unique', 'filter'], 'mathMap' => ['firstline'], 'validMap' => []],
			`order_no` varchar(50) DEFAULT NULL COMMENT '?????????',
			`order_created_date` datetime DEFAULT NULL COMMENT '??????????????????',
			`start_date` timestamp NOT NULL COMMENT '???????????????',
			`end_date` timestamp NOT NULL COMMENT '???????????????',
			`deposit_date` timestamp NOT NULL COMMENT '?????????',
			`paid_on_date` datetime DEFAULT NULL COMMENT '????????????',
			`amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '????????????',
			`currency_cd` char(10) DEFAULT NULL COMMENT '??????',
			`payment_method` varchar(20) DEFAULT NULL COMMENT '????????????',
			
			// goods
			`goods_name` varchar(200) DEFAULT '' COMMENT '?????????',
			`sku_id` varchar(20) DEFAULT '' COMMENT 'sku',
			`plat_goods_id` varchar(20) DEFAULT '' COMMENT '???????????????',
			`our_goods_id` varchar(20) DEFAULT '' COMMENT '???????????????',
			`other_sku_id` varchar(20) DEFAULT NULL COMMENT '?????????erp??????????????????sku',

			// sell
			`goods_number` int(10) unsigned DEFAULT NULL COMMENT '????????????',
			`sale_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '????????????',
			`our_discount_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '????????????????????????',
			`our_coupon_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????????????????',
			`our_integral_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '????????????????????????',
			`our_bind_sale_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '??????????????????????????????',
			`shared_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????????????????????????????????????????',
			`plat_discount_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '????????????????????????',
			`plat_coupon_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????????????????',
			`plat_integral_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '????????????????????????',
			`plat_bind_sale_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '??????????????????????????????',
			`credit_card_dealer_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????????????????',
			`buyer_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '?????????????????????',
			`buyer_amount_tax` decimal(20,2) unsigned DEFAULT NULL COMMENT '????????????????????????',
			`our_cost_of_buyer_amount_tax` decimal(20,2) unsigned DEFAULT NULL COMMENT '?????????????????????????????????',
			`buyer_cost_of_buyer_amount_tax` decimal(20,2) unsigned DEFAULT NULL COMMENT '??????????????????????????????',
			`palat_collection_buyer_amount_tax` decimal(20,2) unsigned DEFAULT NULL COMMENT '??????????????????????????????????????????',
			`palat_payment_buyer_amount_tax` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????????????????????????????????????????',
			`plat_collection_drawback` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????????????????',
			`plat_collection_net_tax` decimal(20,2) unsigned DEFAULT NULL COMMENT '?????????????????????????????????????????????',
			`sale_amount_excluding_tax` decimal(20,2) unsigned DEFAULT NULL COMMENT '??????????????????????????????',
			`commission` decimal(20,2) unsigned DEFAULT NULL COMMENT '??????????????????',
			`our_collection_of_cost` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????????????????/?????????????????????',
			`our_payment_of_cost` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????????????????/?????????????????????',

			// ship
			`shipped_date` datetime DEFAULT NULL COMMENT '?????????',
			`confirmed_date` datetime DEFAULT NULL COMMENT '?????????',
			`buyer_freight` decimal(10,2) unsigned DEFAULT NULL COMMENT '??????????????????',
			`our_payment_plat_freight` decimal(10,2) unsigned DEFAULT NULL COMMENT '??????????????????????????????',
			`our_collection_plat_freight` decimal(10,2) unsigned DEFAULT NULL COMMENT '??????????????????????????????',
			`our_collection_buyer_freight_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '???????????????????????????????????????',
			`our_payment_buyer_freight_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '????????????????????????????????????????????????',
			`our_collection_buyer_cost_charge` decimal(10,2) unsigned DEFAULT NULL COMMENT '?????????????????????????????????',
			`our_collection_buyer_service_cost_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '???????????????????????????????????????',
			`our_payment_buyer_service_cost_and_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '???????????????????????????????????????????????????',
			`plat_service_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '?????????????????????',
			`plat_pack_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '??????????????????',
			`plat_weight_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '???????????????',
			`plat_warheouse_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '???????????????',
			`plat_inventory_destruction_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '?????????????????????',
			`plat_stock_transfer_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '?????????????????????',
			`distribution_cost` decimal(10,2) unsigned DEFAULT NULL COMMENT '????????????????????????',

			// return
			`return_date` date DEFAULT NULL COMMENT '?????????',
			`return_rate` tinyint(3) unsigned DEFAULT '0' COMMENT '?????????',
			`refund_date` date DEFAULT NULL COMMENT '???????????????',
			`return_number` int(10) unsigned DEFAULT NULL COMMENT '????????????',
			`refund` decimal(20,0) unsigned DEFAULT '0' COMMENT '????????????',
			`our_discount_amount_return` decimal(20,2) unsigned DEFAULT '0.00' COMMENT '??????????????????????????????',
			`our_coupon_amount_return` decimal(20,2) unsigned DEFAULT '0.00' COMMENT '?????????????????????????????????',
			`our_integral_amount_return` decimal(20,2) unsigned DEFAULT '0.00' COMMENT '??????????????????????????????',
			`our_bind_sale_amount_return` decimal(20,2) unsigned DEFAULT NULL COMMENT '????????????????????????????????????',
			`our_cost_of_buyer_amount_tax_return` decimal(20,2) unsigned DEFAULT NULL COMMENT '?????????????????????????????????',
			`buyer_amount_return` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????????????????',
			`commission_return` decimal(20,2) unsigned DEFAULT NULL COMMENT '??????????????????',
			`service_cost_return` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????????????????',
			`retrun_service_cost` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????',
			`return_service_amount` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????',
			`buyer_freight_return` decimal(10,2) unsigned DEFAULT NULL COMMENT '????????????????????????',
			`amount_return` decimal(20,2) unsigned DEFAULT NULL COMMENT '????????????????????????',
			`our_payment_plat_freight` decimal(10,2) unsigned DEFAULT NULL COMMENT '??????????????????????????????',
			`our_collection_plat_freight` decimal(10,2) unsigned DEFAULT NULL COMMENT '??????????????????????????????',
			`our_collection_buyer_service_cost_and_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '??????????????????????????????????????????',
			`our_payment_buyer_cost_charge` decimal(10,2) unsigned DEFAULT NULL COMMENT '??????????????????????????????????????????',
			`our_payment_buyer_service_cost_tax` decimal(10,2) unsigned DEFAULT NULL COMMENT '????????????????????????????????????????????????',
			`our_collection_of_cost` decimal(20,2) unsigned DEFAULT NULL COMMENT '???????????????????????????????????????',*/
		];
		return $mapping; 
	}
}