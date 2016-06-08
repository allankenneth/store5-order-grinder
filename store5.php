<?php 
/*
Plugin Name: Store5 Order Grinder
Plugin URI: http://store5.ca
Description: Takes entries from a Gravity Form and combines them with order details exported from Merchant POS and creates WooCommerce orders from the result.
Author: Allan Haggett
Version: 1.0
Author URI: http://allankenneth.com
Requires at least: 4.0.1
Tested up to: 4.3
Stable tag: 4.3
License: GPLv3 or later License
URI: http://www.gnu.org/licenses/gpl-3.0.html
WC requires at least: 2.2
WC tested up to: 2.3
*/
//add_filter('show_admin_bar', '__return_false');
add_action( 'admin_menu', 'store5_menu' );
function store5_menu() {
	add_submenu_page(
		'woocommerce',
		__( 'Order Grinder', 's5-order-grinder' ),
		__( 'Order Grinder', 's5-order-grinder' ),
		'manage_woocommerce',
		'store5',
		'store5_options'
	);
}
function store5_options() {
	//if ( !current_user_can( 'manage_options' ) )  {
	//	wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	//}
	?>
	<h1>Order Grinder</h1>
	<p>Combine Merchant POS Transaction Journals with <a href="http://woothemes.com/woocommerce">Woocommerce</a>.</p>
	<div style="float: left; margin: 1%; padding: 1%; width: 70%;">	
	<?php $form_id = '1'; ?>
	<?php $search_criteria['field_filters'][] = array( 'key' => '13', 'value' => 'Unprocessed' ); ?>
	<?php $entry = GFAPI::get_entries( $form_id, $search_criteria ); ?>
	<?php $flip = array_reverse($entry) ?>
	<?php 
	$howmany = count($flip);
	$last = $howmany - 1;
	$firstdate = explode(' ', $flip[0]['date_created']);
	$lastdate = explode(' ', $flip[$last]['date_created']);

	?>
	<?php foreach($flip as $order) : ?>
		<h1><?php echo $order['id'] ?>. <?php echo $order['14'] ?>  <?php echo $order['15'] ?></h1>
		<?php 
		if($order['16']) {
			$bgcolor = '#999';
		} else {
			$bgcolor = '#FFF';
		}
		?>
		<form action="/wp-content/plugins/store5/create.php" 
			class="addorder" 
			method="post" 
			style="background: <?php echo $bgcolor ?>;margin-bottom:30px; padding: 20px;">
		<input type="hidden" name="action" id="action" value="s5_new_order">
		<input type="hidden" name="gf_entry_id" id="gf_entry_id" value="<?php echo $order['id'] ?>">
		<div style="float: left; margin: 1%; padding: 1%; width: 46%;">
		<?php $daydate = explode(' ', $order['date_created']) ?>
		<h2>Date added: <?php echo $daydate[0] ?></h2>
		Transaction ID: <input size="8" type="text" name="transid" id="transid" value="<?php echo $order["5"] ?>"><br>
		<?php 
		// We support having multiple transaction IDs because it frequently happens that people will come back later
		// and add to their order. Here we detect if there are multiple transaction IDs and if there are
		// then we run the handy little mergeOrders($mutli) function and combine the items from each order
		$multitrans = explode(',', $order['5']);
		if(count($multitrans) > 1) { 
			$multi = Array();
			$combine = 1;
			foreach($multitrans as $trans) {
				array_push($multi, store5_getOrderDetails($trans));
			}
			$orderdeets = mergeOrders($multi);
		} else {
			$orderdeets = store5_getOrderDetails($order['5']);
			$combine = 0;
		}

		?>
		<?php if(count($orderdeets[0]['orderitems']) > 0): ?>
		<?php foreach($orderdeets as $detail): ?>
			Colleague: <input type="text" name="colleague" id="colleague" value="<?php echo $detail['colleague'] ?>"><br>
			Ship Fee: $<input type="text" name="shipcharged" id="shipcharged" value="<?php echo $detail['shipcharged'] ?>">
			<h3><?php if($combine) echo 'Combined ' ?>Order Items</h3>
			<ul>
			<?php foreach($detail['orderitems'] as $item) : ?>
			<li>
				<input type="hidden" 
					id="item-<?php echo $item['sku'] ?>"
					name="deets[]" 
					value="<?php echo $item['qty'] . '||' . $item['sku'] ?>">
				<a href="#" class="btn btn-sm removeitem">x</a>
				<a href="#" class="btn btn-sm markpacked">packed</a>
				<span>
				<span class="qty"><?php echo $item['qty'] ?></span> 
					x
				<?php echo $item['sku'] ?>
					-
				<?php echo $item['desc'] ?>
				</span>
			</li>
			<?php endforeach; ?>
			</ul>
			<hr>
		<?php endforeach;?>
		<?php else: ?>
			<p><strong>Could not find this transaction in the current journal. 
				Please upload a new journal within the correct time period.</strong></p>
		<?php endif; ?>
		Notes: <br>
		<textarea name="notes" cols="30" rows="5"><?php echo $order["4"] ?></textarea>
		<br>

		</div>
		<div style="float: left; margin: 1%; padding: 1%; width: 46%;">
		<h3>Address</h3>
		First Name: <input type="text" name="first_name" value="<?php echo $order["14"] ?>"><br>
		Last Name: <input type="text" name="last_name" value="<?php echo $order["15"] ?>"><br>
		Company: <input type="text" name="company" value="<?php echo $order["12"] ?>"><br>
		Address 1: <input type="text" name="address_1" value="<?php echo $order["2.1"] ?>"><br>
		Address 2: <input type="text" name="address_2" value="<?php echo $order["2.2"] ?>"><br>
		City: <input type="text" name="city" value="<?php echo $order["2.3"] ?>"><br>
		State: <input size="8" type="text" name="state" value="<?php echo $order["2.4"] ?>"><br>
		Code: <input type="text" name="code" value="<?php echo $order["2.5"] ?>"><br>
		<?php 
		// WTF GravityForms? Can't use a country code? 
		if($order['2.6'] == 'United States') {
			$cunt = 'US';
		} elseif($order['2.6'] == 'Canada') {
			$cunt = 'CA';
		} else {
			$cunt = $order['2.6'];
		} 
		?>
		Country: <input type="text" name="country" value="<?php echo $cunt ?>"><br>
		Phone: <input type="text" name="phone" value="<?php echo $order["7"] ?>"><br>
		Email: <input type="text" name="email" value="<?php echo $order["3"] ?>"><br>
		</div>
		<div style="clear:both"></div>
		<button class="btn btn-success">Process Order</button>
	</form>
	<?php endforeach; ?>
	</div>
	<div style="float: left; margin: 1%; padding: 1%; width: 20%;">
	<h1><?php echo $howmany ?> Orders</h1>
	<p>You will need a "Transaction Journal.csv" spanning the dates: <?php echo $firstdate[0] . ' - ' . $lastdate[0] ?>.</p>
	<form action="/wp-content/plugins/store5-order-grinder/upload.php" method="post" enctype="multipart/form-data" class="up"> 
		<div class="form-group">
			<input type="file" name="myFile" id="myFile"> 
			<button type="submit" class="btn btn-lg btn-warning btn-block" value="Upload">
			Upload Transaction Journal
			</button>
		</div>
	</form>
	<!--	<h3>Instructions</h3>
		<p>In Merchant Back Office, run a <strong>Transaction Journal</strong> report with the following parameters:</p>
		<ul>
			<li>Date Posted should be between the newest and oldest orders</li>
			<li>Journal # &amp; Transaction # fields should both be 1 - 99999999</li>
			<li>Print Summary, Print Drawer Count &amp;Print Detail should all be checked</li>
		</ul>
		<p>When the report comes up, click the export button (very far upper-left). Change the file type to CSV and save it to your desktop.</p>
		<p>Once that's done, come back here, click "Choose File;" locate and "Open" the newly exported "Transaction Journal.csv;" Then click the 'Upload &hellip;' button. </p>-->

	</div>

<style>
.strike { text-decoration: line-through }
.qty { font-size: 22px; line-height: 22px; }
</style>
<?php
}

add_action( 'admin_footer', 's5_new_order_javascript' );
function s5_new_order_javascript() { ?>
	<script type="text/javascript" >
	jQuery(document).ready(function($) {
		$('.addorder').submit(function(e){
			e.preventDefault();
			data = jQuery(this).serialize();
			// console.log(data);
			jQuery.post(ajaxurl, data, function(response) {
				orderdirect = 'https://store5.ca/wp-admin/post.php?post=' + response + '&action=edit';
				window.location.replace(orderdirect);
				//alert(response);
			});
		});
		jQuery('.removeitem').click(function(e){
			e.preventDefault();
			jQuery(this).closest('li').remove();
		});
		jQuery('.markpacked').click(function(e){
			e.preventDefault();
			jQuery(this).siblings('span').toggleClass('strike');
		});
	});
	</script> <?php
}

add_action( 'wp_ajax_s5_new_order', 's5_new_order_callback' );
function s5_new_order_callback() {
	global $wpdb;
	$order = wc_create_order();
	$email = $_POST['email'];
	if($email) {
		if(!email_exists($email)) {
			// If they don't have an account yet, create one
			$newuse = $_POST['first_name'] . '_' . $_POST['last_name'];
			$newuse = strtolower($newuse);
			$newuse = preg_replace('/\s+/', '', $newuse);
			//$newusepass = $newuse . 'saltedcaramel';
			//$newusepass = md5($newusepass);
			$newusepass = '';
			$newcust = wc_create_new_customer($email, $newuse, $newusepass);
		} else {
			// They've got an account, so let's assign the order to them
			$newcust = email_exists($email);
		}
	} else {
		// If there is no email provided at all, then create one from their name
		// TODO Fix this DRY
		$newuse = $_POST['first_name'] . '_' . $_POST['last_name'];
		$newuse = strtolower($newuse);
		$newuse = preg_replace('/\s+/', '', $newuse);
		$localemail = $newuse . '@store5.ca';
		//$newusepass = $newuse . 'saltedcaramel';
		//$newusepass = md5($newusepass);
		$newusepass = '';
		$newcust = wc_create_new_customer($localemail, $newuse, $newusepass);	
	}
	$address = array(
		'first_name' => $_POST['first_name'],
		'last_name'  => $_POST['last_name'],
		'company'    => $_POST['company'],
		'email'      => $_POST['email'],
		'phone'      => $_POST['phone'], 
		'address_1'  => $_POST['address_1'],
		'address_2'  => $_POST['address_2'], 
		'city'       => $_POST['city'],
		'state'      => $_POST['state'],
		'postcode'   => $_POST['code'],
		'country'    => $_POST['country']
	);
	foreach($_POST['deets'] as $product) {
		$pro = explode('||',$product);
		$qty = $pro[0];
		$sku = $pro[1];
		$pnum = wc_get_product_id_by_sku($sku);
		if($pnum) {
			$order->add_product( get_product( $pnum ), $qty );
		}
	}
	$noted = $_POST['notes'] . ' Colleague: ' . $_POST['colleague'] . ', Shipping charged: $' . $_POST['shipcharged'];
	$noted .= ' Transactions ID: ' . $_POST['transid'];
	$order->add_order_note($noted);
	$order->set_address( $address, 'billing' );
	$order->set_address( $address, 'shipping' );
	/*$order->add_shipping((object)array (
		'id' => ,
		'label'    => $selected_shipping_method->title,
		'cost'     => (float)$class_cost,
		'taxes'    => array(),
		'calc_tax'  => 'per_order'
	));
	$order->add_shipping($shippymcshipface);*/
	$order->calculate_totals();
	update_post_meta($order->id, 'transaction_id', $_POST['transid'], true);
	// Associate the order with the Wordpress user
	update_post_meta($order->id, '_customer_user', $newcust );
	// Because woocommerce apparently doesn't sync between order address details and 
	// user profile details I am forced to add these 6,000,000 lines of code 
	// to compensate for this oversight (whether it's got a good reason for it or not)
	update_user_meta($newcust, 'first_name', $_POST['first_name']);
	update_user_meta($newcust, 'last_name', $_POST['last_name']);
	update_user_meta($newcust, 'billing_first_name', $_POST['first_name']);
	update_user_meta($newcust, 'billing_last_name', $_POST['last_name']);
	update_user_meta($newcust, 'billing_company', $_POST['co']); // WTF Where is this? TODO
	update_user_meta($newcust, 'billing_address_2', $_POST['address_2']);
	update_user_meta($newcust, 'billing_address_1', $_POST['address_1']);
	update_user_meta($newcust, 'billing_city', $_POST['city']);
	update_user_meta($newcust, 'billing_state', $_POST['state']);
	update_user_meta($newcust, 'billing_postcode', $_POST['code']);
	update_user_meta($newcust, 'billing_country', $_POST['country']);
	update_user_meta($newcust, 'billing_phone', $_POST['phone']);
	update_user_meta($newcust, 'billing_email', $_POST['email']);
	// Ugh.
	update_user_meta($newcust, 'shipping_first_name', $_POST['first_name']);
	update_user_meta($newcust, 'shipping_last_name', $_POST['last_name']);
	update_user_meta($newcust, 'shipping_company', $_POST['co']); // WTF Where is this? TODO
	update_user_meta($newcust, 'shipping_address_2', $_POST['address_2']);
	update_user_meta($newcust, 'shipping_address_1', $_POST['address_1']);
	update_user_meta($newcust, 'shipping_city', $_POST['city']);
	update_user_meta($newcust, 'shipping_state', $_POST['state']);
	update_user_meta($newcust, 'shipping_postcode', $_POST['code']);
	update_user_meta($newcust, 'shipping_country', $_POST['country']);
	update_user_meta($newcust, 'shipping_phone', $_POST['phone']);
	update_user_meta($newcust, 'shipping_email', $_POST['email']);
	// FINALLY
	// If we do the following payment_complete, then our order is locked and we can't edit
	// it in any way. This is probably good, but until the bugs are worked out, I'm
	// just going to disable it and change statuses manually.
	//
	//$order->payment_complete();
	// Set the form to Shipped 
	$changeformstat = GFAPI::update_entry_field( $_POST['gf_entry_id'], '13', 'Shipped' );
	echo $order->id;
	wp_die(); // this is required to terminate immediately and return a proper response
}

function store5_getOrderDetails($tid) {
	// Pull a specific transaction from 
	$open = "/home/s5/html/wp-content/plugins/store5-order-grinder/files/tj.csv";
	$f = fopen($open, "r");
	if($f){
		$order = Array();
		$orderitems = Array();
		while (($line = fgetcsv($f)) !== false) {
			$itembit = '';
			$sku = '';
			$qty = '';
			$desc = '';
			$count = 0;
			// This is all hilariously hard-coded and fragile, but Merchant's CSV export
			// is a messy pile of steaming crap.
			foreach ($line as $cell) {			
				$itembit = htmlspecialchars($cell);
				if($count == 57) {
					$trans = $itembit;
				}
				if($trans == $tid) {
					if($count == 61) {
						$colleague = $itembit;
					}
					if($count == 59) {
						$date = $itembit;
					}
					if($count == 60) {
						$time = $itembit;
					}
					if($count == 65) {
						$sku = $itembit;
					}
					if($count == 69 && $sku == 9436) {
						$shipcharged = $itembit;
					}
					if($count == 68) {
						$qty = $itembit;
					}
					if($count == 67) {
						$desc = $itembit;
					}
				}
				$count++;
			}
			if($trans == $tid) {
				// Shipping charges show up as a line item with the SKU 9436
				// so we check for a SKU, but then check that it's NOT a 
				// shipping charge
				if($sku && $sku != 9436) {
					array_push($orderitems,['sku' => $sku, 
								'qty' => $qty, 
								'desc' => $desc]);
				}
				$theid = $trans;
				$shipchrg = $shipcharged;
				$orderdate = $date . ' ' . $time;
			}
		}
		array_push($order, ['transID' => $theid,  
					'orderdate' => $orderdate,
					'shipcharged' => $shipchrg, 
					'colleague' => $colleague, 
					'orderitems' => $orderitems]);
		fclose($f);
	} else {
		$order = Array();
	}
	return $order;

}
// When there are multiple transaction IDs on an order, we run through store5_getOrderDetails()
// for each ID and stack the results in a new array which we then pass to this function, where
// we go through and extract the details, combining them into a single packing list
function mergeOrders($multi) {
	$order = Array();
	$mergedItems = Array();
	$shipchrg = 0;
	foreach($multi as $morder) {
		foreach($morder[0]['orderitems'] as $item) {
			$mergedItems[] = $item;
		}
		$colleague = $morder[0]['colleague'];
		$shipchrg = $shipchrg + $morder[0]['shipcharged'];
	}
	array_push($order, ['shipcharged' => $shipchrg, 
				'colleague' => $colleague, 
				'orderitems' => $mergedItems]);
	return $order;
}
