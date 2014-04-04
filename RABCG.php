<?php
$wgExtensionCredits['parserhook'][] = array(
       'name' => 'Restrict access by category and group',
       'author' =>'Andres Orencio Ramirez Perez',
       'url' => 'http://www.mediawiki.org/wiki/Extension:Restrict_access_by_category_and_group',
       'description' => 'Restrict access to pages by users groups and documents categories',
        'version' => 1.03
       );
 
function userCanGrupoCategoria($title, $user, $action, $result) {
	global $wgGroupPermissions;
	global $wgWhitelistRead;
	global $wgLang;
	global $userCanGrupoCategoriaInitializantion;
 
	$userCanGrupoCategoriaInitializantion = userCanGrupoCategoriaInitialize();
 
	$categoriaValida = false;
	$existeGrupo = false;
	$docPoseeCategorias = false;
	$categoriaPrivada = false;
	$tmpCatP = false;
	$catnom = $wgLang->getNsText ( NS_CATEGORY );
	$pagBlanca = true;
 
	// System categories
	$systemCat = array();
	foreach( array_change_key_case($title->getParentCategories(), CASE_LOWER) as $key => $value ) {
	        $formatedKey = substr($key, (strpos($key, ":") + 1));
	        $systemCat[$formatedKey] = $value;
	}
 
	// Is this page a white page?
	if (isset($wgWhitelistRead[0])) {
		$pagBlanca = in_array($title, $wgWhitelistRead);
	}
 
	// If document has not category, it's public.
	if (count($title->getParentCategories()) == 0) {
		$categoriaValida = true;
	} else {
		// For each system categories
		foreach( $wgGroupPermissions as $key => $value ) {
			// If current system category is defined as private, then tmpCatP is true
			if (isset($wgGroupPermissions[$key]['private'])) {
				$tmpCatP = $wgGroupPermissions[$key]['private'];
			} else {
				$tmpCatP = false;
			}			
			// If current system category exist in the document category array ...
			if ((array_key_exists(strtolower(str_replace(" ", "_", $key)), $systemCat))) {
				// If          
				if ($tmpCatP && (! $categoriaPrivada)) {
					$categoriaPrivada = true;
					$categoriaValida = false;
				}
				// We see that the user belongs to one of the groups (like of category).
				if ((in_array($key, $user->getGroups())) && ((! $categoriaPrivada) || ($tmpCatP && $categoriaPrivada))) {
					$categoriaValida = true;
				}
				$existeGrupo = true;
			}
		}
		$docPoseeCategorias = (count($title->getParentCategories()) > 0);
	}
	// If groups don't exists and it isn't white page and doc has categories, this doc is a plublic doc. 
	if ((! $existeGrupo) && (! $pagBlanca) && ($docPoseeCategorias))
		$result = true;
        // else If document hasn't got category
        else if ((! $docPoseeCategorias))
		$result = true;
	// If user is logged and user has valid group, or it's white page, this is an accesible doc.
	else if (($user->isLoggedIn() && $categoriaValida) || $pagBlanca)
		$result = true;
	// Else you cannot acces to this doc.
	else
		$result = false;
	return $result;
}
 
function userCanGrupoCategoriaInitialize() {
	global $wgHooks;
	global $wgContLang;
	global $wgWhitelistRead;
	global $wgVersion;
 
	// Defines whitepages (MainPage, Login and LogOut), allways accessibles.
	$wgWhitelistRead[] = $wgContLang->specialPage('Userlogin');
	$wgWhitelistRead[] = $wgContLang->specialPage('Userlogout');
 
	if ($wgVersion >= '1.17') {
		$wgWhitelistRead[] = wfMessage('mainpage')->plain();
	} else {
		$wgWhitelistRead[] = wfMsgForContent('mainpage');
	}
 
	return true;
}
 
$wgHooks['userCan'][] = 'userCanGrupoCategoria';
require_once $IP."/extensions/rabcg/groups.php";