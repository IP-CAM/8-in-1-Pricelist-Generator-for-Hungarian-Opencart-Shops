<?php 


//////////// 		   A megvásárlásával INGYENES TECHNIKAI   			///////////////////
////////////    			SEGÍTSÉGNYÚJTÁSRA JOGOSUL					///////////////////
////////////	Kapcsolat során kérem küldje el a vásárlás során kapott ///////////////////
////////////	Invoice számla számot amit a hivatalos opencart.com 	///////////////////
////////////	 		állított Ki Önnek pl: INV123456 				///////////////////
////////////		Elérhetőségek: Gégény Richárd +36-30-499-5980		///////////////////
////////////		          gegenyrichard@netra.hu 			 		///////////////////
////////////		     https://www.renovatiomarketing.hu 				///////////////////


/********* BEÁLLÍTÁSOK *********/

//Itt adhatja meg üzlete alap beállításait
//FONTOS! 
//EZT A FILET HA SZERKESZTI MINDEN ESETBEN A KÓDOLÁSA ANSI as UTF-8 / UTF-8 BOM nélkül MARADJON! 
//KIZÁLAG NUMERIKUS ÉRTÉKEKET ÍRJON IDE 0-9 KÖZÖTT! HA MÁST ÍRN NEM FOG MŰKÖDNI A PROGRAM!
$config_store_id = 0; 	//Üzletének ha több is van az azonosítója ez alapértelmezetten 0
$shipping_price = 1790; 	//itt adja meg a minimális bruttó szállítási díjat 
$delivery_time = 2; 	//szállítási idő napokban. 
$pickpack = 1; 			//pick-pack ponton átvehetőek a termékei 1-igen 0-nem? 
$warranty = 24; 		//átlgos vagy fix garancia a termékekre hónapokban, ha nincs akkor 0 -át írjon.
$customer_group_id = 8; //Akciós árnál melyik vásárlói csoport árát vegye figyelembe? 
						//Ha több csoportal is rendelkezik pl törzsvásárlók, alapértelmezettek..stb. 
						//itt adhatja meg melyik csoport akciós árát használja a file.
						//Az alapértelemezett csoport azonosítója 1.
$text_pieces = 20;		//hány darab szót rakjon össze a termék leírásban?
						//a program a termék leírást lerövidíti és nem karaktereket számol hanem szavakat.
						//például 20-nál ami rövidebb mint 20 szó az teljes hosszában lesz mentve
						//amit több mint 20 szó az le lesz rövidítve.
							
							//szűrés raktár készlet információ alajpán
$stock_status = array();	//vesszővel elválasztva kell megadni pl.: ..array(1,2,3);
							//a leggyorsab visszakeresése admin panelen az adott raktár
							//állapot szerekesztésénél a linkben látható a legvégén: ...&stock_status_id=6
							//ha üresen marad : array() 
							//akkor nem fogja ezt figyelembe venni a kód.

$category_filter = array();	//Kizárás kategória azonosító alapján!
							//Hasonlóan a raktár készlethez kell megadni  pl.: ..array(1,2,3);	
							//A termék kategória azonosítókat amiket NEM szeretnénk megjeleníteni!
							//hasonlóan az előzőhöz admin panelen a kategória szerkesztésénél látható a linkben ez a szám... &category_id=6
						
//sávos szállítási díjak a termék árától függően!
//ezt a változót töltse fel a legolcsóbb szállítási díjtól visszafelé.
//A mintában látható érétkek így néznének ki egy webshopon:
//	0-9000: 990 Ft
//	9001-16000: 690 Ft
//	16001: ingyenes

$shipping_prices = array(
	"10000000000"	=>"0",
	"19999"			=>"1790"

);

//HA nem szeretné használni a sávos szállítási djíakat, akkor a következőt sort vegye ki a megjegyzésből:
//$shipping_prices = "";						

$PDO_connect = true;   // PDO adatbázis kapcsolat? true = igen ; false = hagyományos mysql_connect.

//sortörések és sepicális karaterek eltávolítása átalakítása
//amenniyben észlel hibás HTML vagy egyéb karaktereket a fileokban itt felveheti megadhatja hogy alakítsa át a program.

//sortörések eltávolítása.
$linebrakes = array("<br/>","\r\n","\r","\n"); 

//Karakterek amit át kell alakítnai...
$hun_html = array("&ouml;","&Ouml;","&uuml;","&Uuml;","&eacute;","&Eacute;","&iacute;","&Iacute;","&Aacute;","&aacute;","&oacute;","&Oacute;","&uacute;","&Uacute;");
//ilyen karakterekre.
$hun_real = array("ö","Ö","ü","Ü","é","É","i","Í","Á","á","ó","Ó","ú","Ú");


/*********       BEÁLLÍTÁSOK VÉGE      *********/
/********* INNEN NE MÓDOSÍTSA A FILET *********/
?>