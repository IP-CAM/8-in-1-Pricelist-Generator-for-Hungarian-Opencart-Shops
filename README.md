# 8 az 1 ben arlista XML opencart
8 az 1 -ben árlista generátor magyar webshopok számára.

XML formátumok:
- Árgép
- Olcsó
- Olcsóbbat
- Kirakat
- Árukereső
- Árközpont
- Összehasonlítom
- italkereso.hu

Ez a modul 2014.-ben került először a kiadásra azóta rengeteg fejlesztésen esett át.

2022.-ben a támogatása megszűnik és ingyenessé vált.

# Kompatiblitás

- PHP 5-8 
- Opencart 1-3x minden verzióval.

# Telepítés 

1. Az upload mappa tartalmát másold fel a webáruház főkönyvtárába
2. Az upload/admin/priceg_config.php filet nyisd meg és a beállításokat írd át.
3. Innen már használható is, paraméteres link meghívással
4. Ha szeretnéd a webáruház menü során a linkeket megjeleníteni telepítsd az /install_admin_link/ mappában a megfelelő modifickációt
( 3x verzióhoz elérhető developer ocmod is, amit a system mappába kell csak másolni)

# Paraméterek példák
[ domain ]/admin/price_generator.php?argep=1
Ez létre hoz az admin mappában egy argep.xml filet.

További leírás, infó:
www.opencart.com/index.php?route=marketplace/extension/info&extension_id=19963

