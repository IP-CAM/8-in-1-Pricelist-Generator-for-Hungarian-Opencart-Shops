<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once 'config.php';
require_once 'priceg_config.php';

//establish db connection...
if($PDO_connect == false){
	$link = mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD) or die('Could not connect to mysql server.' );
	mysql_select_db(DB_DATABASE, $link) or die('Could not select database.');
	mysql_set_charset('utf8');
}else{
	$dsn = 'mysql:dbname='.DB_DATABASE.';host='.DB_HOSTNAME.';charset=utf8';
	$user = DB_USERNAME;
	$password = DB_PASSWORD;

	try {
		$db = new PDO($dsn, $user, $password);
	} catch (PDOException $e) {
		echo 'Connection failed: ' . $e->getMessage();
	}
}


/********* BEÁLLÍTÁSOK PAREMÉTEREK LEKÉRÉSE *********/

$seo_q = "SELECT value FROM  ".DB_PREFIX."setting WHERE store_id = '$config_store_id' AND `key` LIKE '%config_seo_url%'";

if($PDO_connect == false){
	$seo_enabled = mysql_result(mysql_query($seo_q),0);
}else{
	$stmt = $db->query($seo_q);
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$seo_enabled = $result[0]['value'];
}

//-------------

$conf_l_q = "SELECT value FROM  ".DB_PREFIX."setting WHERE `key` LIKE '%config_language%' AND store_id = '$config_store_id'";

if($PDO_connect == false){
	$config_language_code = mysql_result(mysql_query($conf_l_q),0);
}else{
	$stmt = $db->query($conf_l_q);
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$config_language_code = $result[0]['value'];
}

//-------------

$condf_l_id = "SELECT language_id FROM  ".DB_PREFIX."language WHERE `code` LIKE '%$config_language_code%'";

if($PDO_connect == false){
	$config_language_id = mysql_result(mysql_query($condf_l_id),0);
}else{
	$stmt = $db->query($condf_l_id);
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$config_language_id = $result[0]['language_id'];
}


/********* NYELV LEKÉRÉSE *********/

$lang_id_q = "SELECT language_id FROM ".DB_PREFIX."language WHERE status = '1' ORDER BY sort_order ASC LIMIT 1";

if($PDO_connect == false){
	$lang_q = mysql_query($lang_id_q);
	if (mysql_num_rows($lang_q) == 1){
		$config_lang = mysql_result($lang_q,0);
	}else{
		echo "LANGUAGE ERROR!";exit();
	}
}else{
	$stmt = $db->query($lang_id_q);
	$row_count = $stmt->rowCount();
	
	if($row_count == 1){
		$stmt = $db->query($lang_id_q);
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$config_lang = $result[0]['language_id'];
	}else{
		echo "LANGUAGE ERROR!";exit();	
	}
}

/********* RAKTÁR ÁLLAPOTOK *********/

$stock_ids = "SELECT * FROM  ".DB_PREFIX."stock_status WHERE language_id = '".$config_lang."'";

if($PDO_connect == false){
	$stock_query = mysql_query($stock_ids);
	while ($stock = mysql_fetch_array($stock_query)) {
		$stock_staus[$stock['stock_status_id']] = $stock['name'];
	}
}else{
		$stmt = $db->query($stock_ids);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$stock_staus[$row['stock_status_id']] = $row['name'];
		}
}

/********* ADÓK DEFAULT GEO ZÓNA SZERINT!*********/

$zonak_q = "SELECT s.value, zg.geo_zone_id
							FROM ".DB_PREFIX."setting s
							LEFT JOIN ".DB_PREFIX."zone z ON z.zone_id = s.value
							LEFT JOIN ".DB_PREFIX."zone_to_geo_zone zg ON zg.country_id = z.country_id
							WHERE s.key LIKE '%config_zone_id%'";

if($PDO_connect == false){
	$zonak = mysql_query($zonak_q);
	while ($zones = mysql_fetch_array($zonak)) {
		$zona_idk[] = DB_PREFIX."tax_rate.geo_zone_id='".$zones['geo_zone_id']."'";
	}
}else{
		$stmt = $db->query($zonak_q);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$zona_idk[] = DB_PREFIX."tax_rate.geo_zone_id='".$row['geo_zone_id']."'";
		}
}

$zone_sql_where = implode(" OR ",$zona_idk);

/// -----

$ado_q_ids = "SELECT * FROM ".DB_PREFIX."tax_rule 
							LEFT JOIN ".DB_PREFIX."tax_rate ON ".DB_PREFIX."tax_rule.tax_rate_id = ".DB_PREFIX."tax_rate.tax_rate_id 
							WHERE $zone_sql_where
							ORDER BY ".DB_PREFIX."tax_rule.priority ASC";

if($PDO_connect == false){
	$ado_query = mysql_query($ado_q_ids);

	while ($included_ado = mysql_fetch_array($ado_query)) {
		$inc_ado[$included_ado['tax_class_id']][$included_ado['type']][] = $included_ado['rate'];
	}
}else{
		$stmt = $db->query($ado_q_ids);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$inc_ado[$row['tax_class_id']][$row['type']][] = $row['rate'];
		}
}

/********* GYÁRTÓK *********/
$m_q = "SELECT manufacturer_id,name FROM ".DB_PREFIX."manufacturer";

if($PDO_connect == false){
	$manufact_query = mysql_query($m_q);

	while ($included_manufact = mysql_fetch_array($manufact_query)) {
		$inc_manufact[$included_manufact['manufacturer_id']] = $included_manufact['name'];
	}
}else{
		$stmt = $db->query($m_q);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$inc_manufact[$row['manufacturer_id']] = $row['name'];
		}
}


/********* Stock Status... *********/

if (count($stock_status) != 0){
	foreach ($stock_status as &$value) {
		$stock_statuses[]= " p.stock_status_id = '".$value."'";
		}
		$stock_status_array = 'AND ('.implode(' OR ',$stock_statuses).' )';
}else{
	$stock_status_array = "";
}

/********* TERMÉKEK *********/
$prod_q = "SELECT 
			p.product_id,
			p.model,
			p.quantity, 
			p.stock_status_id,
			p.image,
			p.manufacturer_id,
			p.price,
			p.stock_status_id,
			
			(SELECT 
			IF(((s.date_start = '0000-00-00' OR s.date_start < NOW()) AND (s.date_end = '0000-00-00' OR s.date_end > NOW())) , s.price, NULL) as price
			FROM ".DB_PREFIX."product_special s 
			WHERE s.product_id = p.product_id
			AND s.customer_group_id = '$customer_group_id' 
			ORDER BY s.priority ASC LIMIT 0,1) as special_price,
		
			p.tax_class_id,
			p.ean,
			p.isbn,
			
			d.name,
			d.description,
			
			(SELECT 
			GROUP_CONCAT(category_id SEPARATOR ',') 
			FROM ".DB_PREFIX."product_to_category pc 
			WHERE p.product_id = pc.product_id) as categories
			
			FROM ".DB_PREFIX."product p
			LEFT JOIN ".DB_PREFIX."product_description d ON d.product_id = p.product_id
			LEFT JOIN ".DB_PREFIX."product_to_store pts ON pts.product_id = p.product_id
		
			WHERE
			p.status = '1' AND
			d.language_id = '$config_language_id' AND
			
			pts.store_id= '$config_store_id'".$stock_status_array;

if($PDO_connect == false){			
	$products_query = mysql_query($prod_q) or die(mysql_error());
}else{
	$stmt = $db->query($prod_q);
}

	$image_relative = array_filter(explode("/",DIR_IMAGE));
	$last_element = count($image_relative);
	$image_dir_name = $image_relative[$last_element]."/";

if (isset($_GET['argep'])){
/********* XML nyitás ÁRGÉP*********/	
$xml_argep = new DOMDocument("1.0","UTF-8");
$root_argep = $xml_argep ->createElement("termeklista");
$xml_argep ->appendChild($root_argep);	
}

if (isset($_GET['kirakat'])){
/********* Olcsóbbat.hu/Kirakat.hu XML *********/	
$xml_kirakat = new DOMDocument("1.0","UTF-8");
$root_kirakat = $xml_kirakat ->createElement("catalog");
$xml_kirakat ->appendChild($root_kirakat);	
}

if (isset($_GET['arukereso'])){
/********* Árukereső XML *********/	
$xml_arukereso = new DOMDocument("1.0","UTF-8");
$root_arukereso = $xml_arukereso ->createElement("products");
$xml_arukereso ->appendChild($root_arukereso);	
}

if (isset($_GET['joaron'])){
/********* Jo-aron csv *********/	
$lista_neve = 'joaron.csv'; 
$filename = $lista_neve; 

	if (!$handle = fopen($filename, 'w')) { 
			 echo "Cannot open file ($filename)"; 
			 exit; 
	} 
//Mezőnevek első sorhoz adása. 
$list[] = array('id','manufacturer', 'name', 'category', 'description', 'price' , 'product_url', 'image_url', 'delivery_cost', 'delivery_time');
}


if (isset($_GET['olcso'])){
/********* Olcso.hu XMl *********/	
$xml_olcso = new DOMDocument("1.0","UTF-8");
$root_olcso = $xml_olcso ->createElement("products");
$xml_olcso ->appendChild($root_olcso);	
}

/********* VÁLTOZÓK FELTÖLTÉSE *********/		

if($PDO_connect == false) {
	$datatype = mysql_fetch_assoc($products_query);
	while($datatype = mysql_fetch_assoc($products_query)){
		$datass[] = $datatype;
	}
}else{
	$datatype = $stmt->fetch(PDO::FETCH_ASSOC);
	while($datatype = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$datass[] = $datatype;
	}
}


foreach($datass as $products){ 

/**kategkizárás**/
$kihagy = FALSE;

if ($products['categories'] != "" AND count($category_filter) != 0){
	$categs = explode(",",$products['categories']);
	
	foreach ($category_filter as $keresd) {
		$kizart_kateg = array_search($keresd,$categs);
		//echo $kizart_kateg." | ";
		if (!empty($kizart_kateg) OR $products['categories'] == $keresd){
			$kihagy = TRUE;
		}
	}

	//echo "kihagy:".$products['product_id']."-".$kihagy."-<hr/>";
	//echo $products['categories']." | ".$products['product_id']." | ".$kihagy."<br/>";
}
if ($kihagy == FALSE){
/**kategkizárás**/

	$product_id 		 = $products['product_id']; 
	$product_model 		 = $products['model']; 
	$product_quantity 	 = $products['quantity'];
	$product_image	 	 = HTTPS_CATALOG.$image_dir_name.$products['image'];

	$product_name 		 = str_replace($hun_html,$hun_real,
								iconv("UTF-8","UTF-8//IGNORE",
									preg_replace('/[\x00-\x1f]/', ' ', 
										strip_tags(
												html_entity_decode(
													$products['name']
												)
											)
										
										)
									)
							);

	

	$product_description_tag =  str_replace($hun_html,$hun_real,
								iconv("UTF-8","UTF-8//IGNORE",
									preg_replace('/[\x00-\x1f]/', ' ', 
										strip_tags(
												html_entity_decode(
													$products['description']
												)
											)
										
										)
									)
							);
								
	unset($product_description_prices);
	$product_description_prices = explode(" ",$product_description_tag);
	
	$count_pices = count($product_description_prices);
	if ($count_pices >= $text_pieces ){
	$product_description = "";
		for ($i=0;$i<=$text_pieces;$i++){
			if(isset($product_description_prices[$i])){
				$product_description.= $product_description_prices[$i]." ";
			}
		}
	}else{
	$product_description = $product_description_tag;
	}
	
	$product_description.="...";
	
	
	if(isset($inc_manufact[$products['manufacturer_id']])){
		$manufacturer		 = str_replace($hun_html,$hun_real,
									iconv("UTF-8","UTF-8//IGNORE",
										preg_replace('/[\x00-\x1f]/', ' ', 
											strip_tags(
													html_entity_decode(
														$inc_manufact[$products['manufacturer_id']]
													)
												)
											
											)
										)
								);
	}else{
		$manufacturer = "";
	}
	


/********* KATEGÓRIA *********/	
$category_hierarchy = array();

$category_sql = "SELECT d.name
							FROM ".DB_PREFIX."product_to_category pc
							LEFT JOIN ".DB_PREFIX."category_description d ON d.category_id = pc.category_id
							LEFT JOIN ".DB_PREFIX."category_path p ON p.category_id = d.category_id
							WHERE pc.product_id = '$product_id'
							ORDER BY p.level ASC";


if($PDO_connect == false){	
	$categroy_q = mysql_query($category_sql);
	
	while($categories = mysql_fetch_assoc($categroy_q)) {
		$category_hierarchy[] = $categories['name'];
	}
}else{
		$stmt = $db->query($category_sql);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$category_hierarchy[] = $row['name'];
		}
}
		if(count($category_hierarchy) >= 1){
			$category_hierarchy = array_unique($category_hierarchy);
			$to_sring_check = implode(" / ",$category_hierarchy);
		}else{
			if(isset($category_hierarchy[0])){
				$to_sring_check = $category_hierarchy[0];
			}else{
				$to_sring_check = "";
			}
		}
		
	$category_names = str_replace($hun_html,$hun_real,
								iconv("UTF-8","UTF-8//IGNORE",
									preg_replace('/[\x00-\x1f]/', ' ', 
										strip_tags(
												html_entity_decode(
													$to_sring_check 
												)
											)
										
										)
									)
							);
							
	$category_names_joaron = str_replace(" / "," > ",$category_names);
	
	if ($seo_enabled == 1){
		
		$clean_sql = "SELECT keyword FROM ".DB_PREFIX."seo_url WHERE query LIKE 'product_id=".$products['product_id']."' AND keyword != '' AND language_id = '".$config_language_id."' AND store_id = '".$config_store_id."'";
		
		if($PDO_connect == false){	
			$clean_search = mysql_query($clean_sql);
		
		if (mysql_num_rows($clean_search) == 1){
			$clean_url = mysql_result($clean_search,0);
			$product_link = HTTPS_CATALOG.$clean_url;
		}else{
			$product_link = HTTPS_CATALOG."index.php?route=product/product&product_id=". $products['product_id'];
		}

		}else{
			$stmt = $db->query($clean_sql);
			
			if ($stmt->rowCount() == 1){
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$clean_url = $results[0]['keyword'];
				$product_link = HTTPS_CATALOG.$clean_url;
			}else{
				$product_link = HTTPS_CATALOG."index.php?route=product/product&product_id=". $products['product_id'];
			}
		}

	}else{
		$product_link = HTTPS_CATALOG."index.php?route=product/product&product_id=". $products['product_id'];
	}

	$tax_total = 0;
	$tax_total_discount = 0;

	//FIX ADÓ.
	if(isset($inc_ado[$products['tax_class_id']]["F"][0])){
		$p_count = count($inc_ado[$products['tax_class_id']]["F"][0]);

		if ($p_count != 0){
			for($i = 0;$i <= $p_count;$i++){
				$tax_total 			= $tax_total + $inc_ado[$products['tax_class_id']]["F"][$i];
				$tax_total_discount = $tax_total_discount + $inc_ado[$products['tax_class_id']]["F"][$i];
			}
		}
	}
	
	//SZÁZALÉKOS ADÓ.
	if(isset($inc_ado[$products['tax_class_id']]["P"][0])){
		if(is_array($inc_ado[$products['tax_class_id']]["P"][0])){
			$p_count = count($inc_ado[$products['tax_class_id']]["P"][0]);
		}else{
			$p_count = 1;
		}

		if ($p_count != 0){
			for($i = 0;$i <= $p_count;$i++){
				if (!empty($inc_ado[$products['tax_class_id']]["P"][$i])){
					$tax_total 			= $tax_total + (($inc_ado[$products['tax_class_id']]["P"][$i] /100)*$products['price']);
					$tax_total_discount = $tax_total_discount + (($inc_ado[$products['tax_class_id']]["P"][$i] /100)*$products['special_price']);
				}else{
					$tax_total 			= $tax_total + 0;
					$tax_total_discount = $tax_total_discount + 0;		
				}
			}
		}
	}

if ($products['special_price'] != ""){
	$simple_price = number_format($products['special_price'] + $tax_total_discount,0,'','');
	$simple_netto = number_format($products['special_price'],0,'','');
	$originalPrice = number_format($products['price'] + $tax_total,0,'','');
}else{
	$simple_price = number_format($products['price'] + $tax_total,0,'','');
	$simple_netto = number_format($products['price'],0,'','');
	$originalPrice = "";
}

if(isset($shipping_prices)){
foreach ($shipping_prices as $key => $value) {
		if($simple_price <= $key){
			$shipping_price = $value;
			
			if ($shipping_price == 0){
				$shipping_price_arukereso = "FREE";
			}else{
				$shipping_price_arukereso = $shipping_price;
			}
		}
	}
}

if ($product_quantity >= 1){
	$stock_status = 'true';
}else{
	$stock_status = 'false';
}

/********* LISTÁK *********/	

if (isset($_GET['argep'])){
//------------árgép XML-----------------//

$id   = $xml_argep ->createElement("cikkszam");
$idText = $xml_argep ->createCDATASection($product_model);
$id->appendChild($idText);

$title   = $xml_argep ->createElement("nev");
$titleText = $xml_argep ->createCDATASection($product_name);
$title->appendChild($titleText);

$desc   = $xml_argep ->createElement("leiras");
$desc_text = $xml_argep ->createCDATASection($product_description);
$desc->appendChild($desc_text);

$price   = $xml_argep ->createElement("ar");
$price_text = $xml_argep ->createTextNode($simple_price);
$price->appendChild($price_text);

$picture   = $xml_argep ->createElement("fotolink");
$picture_text = $xml_argep ->createCDATASection($product_image);
$picture->appendChild($picture_text);

$url   = $xml_argep ->createElement("termeklink");
$url_text = $xml_argep ->createCDATASection($product_link);
$url->appendChild($url_text);

$ido   = $xml_argep ->createElement("ido");
$ido_text = $xml_argep ->createCDATASection($delivery_time);
$ido->appendChild($ido_text);

$szallitas   = $xml_argep ->createElement("szallitas");
$szallitas_text = $xml_argep ->createTextNode($shipping_price);
$szallitas->appendChild($szallitas_text);

$book = $xml_argep ->createElement("termek");
$book->appendChild($id);
$book->appendChild($title);
$book->appendChild($desc);
$book->appendChild($price);
$book->appendChild($picture);
$book->appendChild($url);
$book->appendChild($ido);
$book->appendChild($szallitas);

$root_argep->appendChild($book);

//------------árgép XML-----------------//
}

if (isset($_GET['kirakat'])){
//------------Olcsóbbat.hu/Kirakat.hu XML-----------------//

$id_kirakat   = $xml_kirakat ->createElement("id");
$idText = $xml_kirakat ->createTextNode($product_id);
$id_kirakat->appendChild($idText);

$manufacturer_kirakat   = $xml_kirakat ->createElement("manufacturer");
$manufacturerText = $xml_kirakat ->createCDATASection($manufacturer);
$manufacturer_kirakat->appendChild($manufacturerText);

$name_kirakat   = $xml_kirakat ->createElement("name");
$nameText = $xml_kirakat ->createCDATASection($product_name);
$name_kirakat->appendChild($nameText);

$netprice_kirakat   = $xml_kirakat ->createElement("netprice");
$netpriceText = $xml_kirakat ->createTextNode($simple_netto);
$netprice_kirakat->appendChild($netpriceText);

$grossprice_kirakat   = $xml_kirakat ->createElement("grossprice");
$grosspriceText = $xml_kirakat ->createTextNode($simple_price);
$grossprice_kirakat->appendChild($grosspriceText);

$grosspricecarriage_kirakat   = $xml_kirakat ->createElement("grosspricecarriage");
$grosspricecarriageText = $xml_kirakat ->createTextNode($simple_price+$shipping_price);
$grosspricecarriage_kirakat->appendChild($grosspricecarriageText);

$deliveryprice_kirakat   = $xml_kirakat ->createElement("deliveryprice");
$deliverypriceText = $xml_kirakat ->createTextNode($shipping_price);
$deliveryprice_kirakat->appendChild($deliverypriceText);

$deliverytime_kirakat   = $xml_kirakat ->createElement("deliverytime");
$deliverytimeText = $xml_kirakat ->createTextNode($delivery_time);
$deliverytime_kirakat->appendChild($deliverytimeText);

$pickpackpoint_kirakat   = $xml_kirakat ->createElement("pickpackpoint");
$pickpackpointText = $xml_kirakat ->createTextNode($pickpack);
$pickpackpoint_kirakat->appendChild($pickpackpointText);

$warranty_kirakat   = $xml_kirakat ->createElement("warranty");
$warrantyText = $xml_kirakat ->createTextNode($warranty);
$warranty_kirakat->appendChild($warrantyText);

$stock_kirakat   = $xml_kirakat ->createElement("stock");
$stockText = $xml_kirakat ->createTextNode($stock_status);
$stock_kirakat->appendChild($stockText);

$itemid_kirakat  = $xml_kirakat ->createElement("itemid");
$itemidText = $xml_kirakat ->createTextNode($product_model);
$itemid_kirakat->appendChild($itemidText);

$url_kirakat   = $xml_kirakat ->createElement("urlsite");
$url_text = $xml_kirakat ->createCDATASection($product_link);
$url_kirakat->appendChild($url_text);

$picture_kirakat   = $xml_kirakat ->createElement("urlpicture");
$picture_text = $xml_kirakat ->createCDATASection($product_image);
$picture_kirakat->appendChild($picture_text);

$desc_kirakat   = $xml_kirakat ->createElement("describe");
$desc_text = $xml_kirakat ->createCDATASection($product_description);
$desc_kirakat->appendChild($desc_text);

$category_kirakat   = $xml_kirakat ->createElement("category");
$category_text = $xml_kirakat ->createCDATASection($category_names);
$category_kirakat->appendChild($category_text);

$book1 = $xml_kirakat ->createElement("product");
$book1->appendChild($id_kirakat);
$book1->appendChild($manufacturer_kirakat);


if ($products['isbn'] != "" OR $products['ean'] != ""){

	$barcodes = $xml_kirakat ->createElement("barcodes");

		if($products['isbn'] != ""){
		$isbn_kirakat   = $xml_kirakat ->createElement("barcode");
		$isbnText = $xml_kirakat ->createCDATASection($products['isbn']);
		$isbn_kirakat->appendChild($isbnText);
		$isbn_kirakat -> setAttribute('type', 'isbn');
		$barcodes->appendChild($isbn_kirakat);
		}
		
		if($products['ean'] != ""){
		$ean_kirakat   = $xml_kirakat ->createElement("barcode");
		$eanText = $xml_kirakat ->createCDATASection($products['ean']);
		$ean_kirakat->appendChild($eanText);
		$ean_kirakat -> setAttribute('type', 'ean-13');
		$barcodes->appendChild($ean_kirakat);
		}
		
	$book1->appendChild($barcodes);	
}

$book1->appendChild($name_kirakat);
$book1->appendChild($netprice_kirakat);
$book1->appendChild($grossprice_kirakat);
$book1->appendChild($grosspricecarriage_kirakat);
$book1->appendChild($deliveryprice_kirakat);
$book1->appendChild($deliverytime_kirakat);
$book1->appendChild($pickpackpoint_kirakat);
$book1->appendChild($warranty_kirakat);
$book1->appendChild($stock_kirakat);
$book1->appendChild($itemid_kirakat);
$book1->appendChild($url_kirakat);
$book1->appendChild($picture_kirakat);
$book1->appendChild($desc_kirakat);
$book1->appendChild($category_kirakat);

$root_kirakat->appendChild($book1);

//------------Olcsóbbat.hu/Kirakat.hu XML-----------------//
}

if (isset($_GET['arukereso'])){
//------------Árukereső XML-----------------//

$indentifier_arukereso   = $xml_arukereso ->createElement("identifier");
$indentifierText = $xml_arukereso ->createTextNode($product_id);
$indentifier_arukereso->appendChild($indentifierText);

$manufacturer_arukereso   = $xml_arukereso ->createElement("manufacturer");
$manufacturerText = $xml_arukereso ->createTextNode($manufacturer);
$manufacturer_arukereso->appendChild($manufacturerText);

$name_arukereso   = $xml_arukereso ->createElement("name");
$nameText = $xml_arukereso ->createTextNode($product_name);
$name_arukereso->appendChild($nameText);

$category_arukereso   = $xml_arukereso ->createElement("category");
$category_text = $xml_arukereso ->createTextNode($category_names);
$category_arukereso->appendChild($category_text);

$url_arukereso   = $xml_arukereso ->createElement("product_url");
$url_text = $xml_arukereso ->createTextNode($product_link);
$url_arukereso->appendChild($url_text);

$netprice_arukereso   = $xml_arukereso ->createElement("net_price");
$netpricearukereso = $xml_arukereso ->createTextNode($simple_netto);
$netprice_arukereso->appendChild($netpricearukereso);

$grossprice_arukereso   = $xml_arukereso ->createElement("price");
$grosspriceText = $xml_arukereso ->createTextNode($simple_price);
$grossprice_arukereso->appendChild($grosspriceText);

$picture_arukereso   = $xml_arukereso ->createElement("image_url");
$picture_text = $xml_arukereso ->createTextNode($product_image);
$picture_arukereso->appendChild($picture_text);

$desc_arukereso   = $xml_arukereso ->createElement("description");
$desc_text = $xml_arukereso ->createTextNode($product_description);
$desc_arukereso->appendChild($desc_text);

$deliverytime_arukereso   = $xml_arukereso ->createElement("delivery_time");
$deliverytimeText = $xml_arukereso ->createTextNode($delivery_time);
$deliverytime_arukereso->appendChild($deliverytimeText);

$deliveryprice_arukereso   = $xml_arukereso ->createElement("delivery_cost");
$deliverypriceText = $xml_arukereso ->createTextNode($shipping_price_arukereso);
$deliveryprice_arukereso->appendChild($deliverypriceText);

$book2 = $xml_arukereso ->createElement("product");
		
		if($products['ean'] != ""){
		$ean_arukereso   = $xml_arukereso ->createElement("ean_code");
		$eanText =$xml_arukereso ->createTextNode($products['ean']);
		$ean_arukereso->appendChild($eanText);
		$book2->appendChild($ean_arukereso);
		}

$book2->appendChild($indentifier_arukereso);		
$book2->appendChild($manufacturer_arukereso);
$book2->appendChild($name_arukereso);
$book2->appendChild($category_arukereso);
$book2->appendChild($url_arukereso);
$book2->appendChild($netprice_arukereso);
$book2->appendChild($grossprice_arukereso);
$book2->appendChild($picture_arukereso);
$book2->appendChild($desc_arukereso);
$book2->appendChild($deliverytime_arukereso);
$book2->appendChild($deliveryprice_arukereso);

$root_arukereso->appendChild($book2);

//------------Árukereső XML-----------------//
}

//------------Jóáron XML-----------------//
if (isset($_GET['joaron'])){
   $gyarto = mb_convert_encoding($manufacturer,"ISO-8859-2","UTF-8"); 
   $nev = mb_convert_encoding($product_name,"ISO-8859-2","UTF-8");
   $ketegoria = mb_convert_encoding($category_names_joaron,"ISO-8859-2","UTF-8");
   $leiras = mb_convert_encoding($product_description,"ISO-8859-2","UTF-8"); 
   $ara = $simple_price; 
   $termeklink = mb_convert_encoding($product_link,"ISO-8859-2","UTF-8"); 
   $keplink = mb_convert_encoding($product_image,"ISO-8859-2","UTF-8");  
   $szallitasi_dij = $shipping_price; 
   $szallitasi_ido = $delivery_time; 
    
   $list[] = array($product_id,$gyarto, $nev, $ketegoria, $leiras, $ara, $termeklink, $keplink, $szallitasi_dij, $szallitasi_ido); 
   
}
//------------Jóáron XML-----------------//

//------------Olcso XML-----------------//
if (isset($_GET['olcso'])){

$id_olcso   = $xml_olcso ->createElement("id");
$id_Text = $xml_olcso ->createTextNode($products['model']);
$id_olcso->appendChild($id_Text);

$name_olcso   = $xml_olcso ->createElement("name");
$name_Text = $xml_olcso ->createTextNode($product_name);
$name_olcso->appendChild($name_Text);

$price_olcso   = $xml_olcso ->createElement("price");
$price_Text = $xml_olcso ->createTextNode($simple_price);
$price_olcso->appendChild($price_Text);

$url_olcso   = $xml_olcso ->createElement("url");
$url_Text = $xml_olcso ->createTextNode($product_link);
$url_olcso->appendChild($url_Text);

$description_olcso   = $xml_olcso ->createElement("description");
$description_Text = $xml_olcso ->createTextNode($product_description);
$description_olcso->appendChild($description_Text);

$picture_olcso   = $xml_olcso ->createElement("picture");
$picture_Text = $xml_olcso ->createTextNode($product_image);
$picture_olcso->appendChild($picture_Text);

$category_olcso   = $xml_olcso ->createElement("category");
$category_Text = $xml_olcso ->createTextNode($category_names);
$category_olcso->appendChild($category_Text);

$manufacturer_olcso   = $xml_olcso ->createElement("manufacturer");
$manufacturer_Text = $xml_olcso ->createTextNode($manufacturer);
$manufacturer_olcso->appendChild($manufacturer_Text);

$originalPrice_olcso   = $xml_olcso ->createElement("originalPrice");
$originalPrice_Text = $xml_olcso ->createTextNode($originalPrice);
$originalPrice_olcso->appendChild($originalPrice_Text);

$deliveryTime_olcso   = $xml_olcso ->createElement("deliveryTime");
$deliveryTime_Text = $xml_olcso ->createTextNode($delivery_time);
$deliveryTime_olcso->appendChild($deliveryTime_Text);

$deliveryPrice_olcso   = $xml_olcso ->createElement("deliveryPrice");
$deliveryPrice_Text = $xml_olcso ->createTextNode($shipping_price);
$deliveryPrice_olcso->appendChild($deliveryPrice_Text);

$gar_olcso   = $xml_olcso ->createElement("gar");
$garPrice_Text = $xml_olcso ->createTextNode($warranty);
$gar_olcso->appendChild($garPrice_Text);

$book3 = $xml_olcso ->createElement("product");
$book3->appendChild($id_olcso);
$book3->appendChild($name_olcso);
$book3->appendChild($price_olcso);
$book3->appendChild($url_olcso);
$book3->appendChild($description_olcso);
$book3->appendChild($picture_olcso);
$book3->appendChild($category_olcso);
$book3->appendChild($manufacturer_olcso);
$book3->appendChild($originalPrice_olcso);
$book3->appendChild($deliveryTime_olcso);
$book3->appendChild($deliveryPrice_olcso);
$book3->appendChild($gar_olcso);

$root_olcso->appendChild($book3);

}

//------------Olcso XML-----------------//
}//katkizáró vége
}

if (isset($_GET['argep'])){
/********* árgép XML mentés *********/	
$xml_argep ->formatOutput = true;
$xml_argep ->save("argep.xml") or die("HIBA AZ ÁRGÉP FILE ÍRÁSA SORÁN!!!");
}

if (isset($_GET['kirakat'])){
/********* Olcsóbbat.hu/Kirakat.hu XML mentés *********/	
$xml_kirakat ->formatOutput = true;
$xml_kirakat ->save("kirakat.xml") or die("HIBA AZ Olcsóbbat.hu/Kirakat.hu XML FILE ÍRÁSA SORÁN!!!");
}

if (isset($_GET['arukereso'])){
/********* Árukereső XML mentés *********/	
$xml_arukereso ->formatOutput = true;
$xml_arukereso ->save("arukereso.xml") or die("HIBA AZ Árukereső XML FILE ÍRÁSA SORÁN!!!");
}

if (isset($_GET['joaron'])){
/********* Jóáron CSV mentés *********/	
foreach ($list as $fields) { 
    fputcsv($handle, $fields, ";"); 
} 

fclose($handle); 
}

if (isset($_GET['olcso'])){
/********* Olcso XML mentés *********/	
$xml_olcso ->formatOutput = true;
$xml_olcso ->save("olcso.xml") or die("HIBA AZ Olcso XML FILE ÍRÁSA SORÁN!!!");
}

echo "OK";

if($PDO_connect == false){
	mysql_close($link);
}
?>