<?php
$version = '0.3b9';
$page = basename($_SERVER['SCRIPT_NAME']);
$prod = ($page == 'wod2.php' );
$msg_err = array();

$out = array();

/* Compteur */
if (is_file("compteur.txt")) {
	$fp = fopen("compteur.txt","r");
	while (!feof($fp)) { //on parcourt toutes les lignes
	  $compteur = fgets($fp, 4096); // lecture du contenu de la ligne
	  break;
	}
	fclose($fp); // On ferme le fichier
}
if (!$compteur) $compteur = 0;

function utf8_sprintf ($format) {
  $args = func_get_args();
  for ($i = 1; $i < count($args); $i++) {
    $args [$i] = iconv('UTF-8', 'ISO-8859-1', $args [$i]);
  }
  return iconv('ISO-8859-1', 'UTF-8', call_user_func_array('sprintf', $args));
}

function disp($x,$all=false) {
    global $stat_skills,$_POST,$skills;
    if ($all or $skills[$x]) {
        if ($_POST['graf'] == 'on') {
            $m = '[color=#cccc66]|'. str_repeat('-',round($stat_skills[$x]/5)).'[/color]';
            $back = utf8_sprintf("%'.-35s %'--4s%s",$x, $stat_skills[$x],$m);
        } else {
            $back = utf8_sprintf("%'.-35s %s",$x, $stat_skills[$x]);
        }
        $back.= "<br />\n";
    }
    return $back;
}

function hack_ie ($txt = '', $position) {
	if ($position > 1) {
		$txt = trim(substr($txt, 0, $position));
		$arr_txt = explode(' ', $txt);
		array_pop($arr_txt);
		return implode(' ', $arr_txt);
	} else {
		return $txt;
	}
}

$classes = array( "Alchimiste", "Archer", "Barbare", "Barde", "Chaman", "Chasseur", "Chevalier", "Erudit", "Gladiateur", "Jongleur", "Paladin", "Prêtre", "Risque-tout", "Sorcier");
$races = array( "Dinturan", "Elfe Mag-Mor", "Elfe Tirem-Ag", "Frontalier", "Gnome", "Homme des bois", "Kérasi", "Nain des collines", "Nain des montagnes", "Rashani", "Semi-homme");
$mondes = array( 'Ezantoh', 'Allerand', 'Belgaran', 'Al Be Za', 'Al Be', 'Be Za', 'Ya Za'); 
$locs = array( 'tête', 'ceinture', 'torse', 'cou', 'oreilles', 'anneau', 'cape', 'bras', 'main droite', 'main droite main gauche', 'jambes', 'pieds', 'sac', 'décoration');


if (isset($_POST['matos']) and !empty($_POST['matos'])) {
  $raw = trim(stripslashes($_POST['matos']));
  $raw = preg_replace("(\r\nsac)","sacxxx",$raw);

  $pos = strpos($raw,'Objets pris');
  $pos1 = strpos($raw,'sacxxx');

  preg_match_all('/\r\n[0-9]+[ \t]*([^\n\r\t!]*)/',substr($raw,$pos,$pos1-$pos),$match);
  foreach ($match[1] as $n=>$val) {
    $loc = 'Equipements';
    if (trim($val)) {
	  $val = hack_ie($val, strpos($val, 'entrepôt'));

	  // On retire les quantités pour les consommables
	  //$val = preg_replace("(\([0-9]+\/[0-9]+\))", '', $val);
      $matos[$loc][] = trim($val);
      $out[] = trim($val);
    }
  }

  $raw = substr($raw, $pos1);

  $bln_décorations = true;
  $pos = 0;
  $pos1 = strpos($raw,'décoration');

  if (!$pos1) {
	$bln_décorations = false;
	$pos1 = strpos($raw,'[Cacher]');
	if (!$pos1) $pos1 = strpos($raw,'[Afficher]');
  }

  preg_match_all('/\r\n[0-9]+[ \t]*([^\n\r\t!]*)/',substr($raw,$pos,$pos1),$match);
  foreach ($match[1] as $n=>$val) {
    $loc = 'Sac à dos';
    if (trim($val)) {
	  $val = hack_ie($val, strpos($val, 'entrepôt'));

	  // Si consommable, on passe..
	  if (preg_match("(\([0-9]+\/[0-9]+\))", $val)) {
		// On retire les quantités pour les consommables
		$val = preg_replace("(\([0-9]+\/[0-9]+\))", '', $val);
		if ($arr_cons[$val]) $arr_cons[$val] += 1;
		else $arr_cons[$val] = 1;
		continue;
	  }
	  
      $matos[$loc][] = trim($val);
      $out[] = trim($val);
    }
  }

  $raw = substr($raw, $pos1);
  
  if($bln_décorations) {
	  $pos = 0;
	  $pos1 = strpos($raw,'[Cacher]');
	  if (!$pos1) $pos1 = strpos($raw,'[Afficher]');

	  preg_match_all('/\r\n[0-9]+[ \t]*([^\n\r\t!]*)/',substr($raw,$pos,$pos1),$match);
	  foreach ($match[1] as $n=>$val) {
		$loc = 'Décorations';
		if (trim($val)) {
		  $val = hack_ie($val, strpos($val, 'entrepôt'));

		  $matos[$loc][] = trim($val);
		  $out[] = trim($val);
		}
	  }
  }
}

if (isset($_POST['race']) && !empty($_POST['race']) and in_array($_POST['race'],$races)) {
    $race = $out['race'] = $_POST['race'];
} else if (isset($_POST['race'])) {
    /*foreach ($races as $r) {
      if (strpos($line,$r)) {
        $race = $out['race'] = $r;
        break;
      }
    }*/
    $msg_err[] = "race";
}
if (isset($_POST['class']) && in_array($_POST['class'],$classes)) {
    $class = $out['class'] = $_POST['class'];
} else if (isset($_POST['class'])){
    /*foreach ($classes as $c) {
      if (strpos($line,$c)) {
        $class = $out['class'] = $c;
        break;
      }
    }*/
    $msg_err[] = "classe";
}

if (!$msg_err && isset($_POST['attributes']) and !empty($_POST['attributes'])) {
  $raw = trim(stripslashes($_POST['attributes']));
  $line = substr($raw,strpos($raw,'Quitter'),strpos($raw,"Dans le forum"));


  if (!empty($_POST['monde']) and in_array($_POST['monde'],$mondes)) {
    $out['monde'] = $_POST['monde'];
  } else {
    foreach ($mondes as $m) {
      if (strstr($line,$m)) {
        $out['monde'] = $m;
        $line = substr($line,strlen($m));
        break;
      }
    }
  }
  if (strstr($m,' ')) $out['monde'] = '';

  if (!empty($_POST['name'])) {
    $name = $out['name'] = htmlspecialchars($_POST['name']);
  } else {
    $full = trim(substr($raw,0,strpos($raw,' - Attributs')));
    $name = $out['name'] = substr($full,strrpos($full,"\r\n"));
  }
  
  $pos = strpos($raw,'Titre', strpos($raw,'Titre')+5);
  $title = $out['title'] = trim(substr($raw,$pos+6,strpos($raw,'Armure')-$pos-6))!="Choisir un titre"?trim(substr($raw,$pos+6,strpos($raw,'Armure')-$pos-6)):'';

  $pos = strpos($raw,"\r\nAttribut");
  $pos1 = strpos($raw,"Force",$pos);
  $pos2 = strpos($raw,"Constitution",$pos);
  $pos3 = strpos($raw,"Intelligence",$pos);
  $pos4 = strpos($raw,"Adresse",$pos);
  $pos5 = strpos($raw,"Charisme",$pos);
  $pos6 = strpos($raw,"Vitesse",$pos);
  $pos7 = strpos($raw,"Perception",$pos);
  $pos8 = strpos($raw,"Volonté",$pos);
  $pos9 = strpos($raw,"Niveau",$pos);
  preg_match('/([0-9]+)( \[([0-9]+)\])?/',substr($raw,$pos1,$pos2),$match_for); 
  preg_match('/([0-9]+)( \[([0-9]+)\])?/',substr($raw,$pos2,$pos3),$match_con); 
  preg_match('/([0-9]+)( \[([0-9]+)\])?/',substr($raw,$pos3,$pos4),$match_int); 
  preg_match('/([0-9]+)( \[([0-9]+)\])?/',substr($raw,$pos4,$pos5),$match_adr); 
  preg_match('/([0-9]+)( \[([0-9]+)\])?/',substr($raw,$pos5,$pos6),$match_cha); 
  preg_match('/([0-9]+)( \[([0-9]+)\])?/',substr($raw,$pos6,$pos7),$match_vit); 
  preg_match('/([0-9]+)( \[([0-9]+)\])?/',substr($raw,$pos7,$pos8),$match_per); 
  preg_match('/([0-9]+)( \[([0-9]+)\])?/',substr($raw,$pos8,$pos9),$match_vol); 

  $out['for'] = $match_for[1];
  if ($match_for[3]) {
    $att['for'] = $match_for[3];
    $out['for'].= ' ['. $att['for'] .']';
  } else {
    $att['for'] = $match_for[1];
  }

  $out['con'] = $match_con[1];
  if ($match_con[3]) {
    $att['con'] = $match_con[3];
    $out['con'].= ' ['. $att['con'] .']';
  } else {
    $att['con'] = $match_con[1];
  }

  $out['int'] = $match_int[1];
  if ($match_int[3]) {
    $att['int'] = $match_int[3];
    $out['int'].= ' ['. $att['int'] .']';
  } else {
    $att['int'] = $match_int[1];
  }

  $out['adr'] = $match_adr[1];
  if ($match_adr[3]) {
    $att['adr'] = $match_adr[3];
    $out['adr'].= ' ['. $att['adr'] .']';
  } else {
    $att['adr'] = $match_adr[1];
  }

  $out['cha'] = $match_cha[1];
  if ($match_cha[3]) {
    $att['cha'] = $match_cha[3];
    $out['cha'].= ' ['. $att['cha'] .']';
  } else {
    $att['cha'] = $match_cha[1];
  }

  $out['vit'] = $match_vit[1];
  if ($match_vit[3]) {
    $att['vit'] = $match_vit[3];
    $out['vit'].= ' ['. $att['vit'] .']';
  } else {
    $att['vit'] = $match_vit[1];
  }

  $out['per'] = $match_per[1];
  if ($match_per[3]) {
    $att['per'] = $match_per[3];
    $out['per'].= ' ['. $att['per'] .']';
  } else {
    $att['per'] = $match_per[1];
  }

  $out['vol'] = $match_vol[1];
  if ($match_vol[3]) {
    $att['vol'] = $match_vol[3];
    $out['vol'].= ' ['. $att['vol'] .']';
  } else {
    $att['vol'] = $match_vol[1];
  }

  $pos = $pos9;

  preg_match('/([0-9]+)/',substr($raw,$pos,strpos($raw,"\r\n",$pos)),$match);
  $out['niveau'] = $match[1];
  $niv = $out['niveau'];

  $pos1 = strpos($raw,'PV',$pos);
  $pos2 = strpos($raw,'PM',$pos);
  $pos3 = strpos($raw,'Actions',$pos);
  $pos4 = strpos($raw,'Bonus en initiative',$pos);
  $pos5 = strpos($raw,'Points',$pos);
  preg_match('/([0-9]+)( \[([0-9]+)\])?\s*Régén. PV ?: *([0-9]+)( \[([-0-9]+)\])?/',substr($raw,$pos1,$pos2),$match_hp);
  preg_match('/([0-9]+)( \[([0-9]+)\])?\s*Régén. PM ?: *([0-9]+)( \[([-0-9]+)\])?/',substr($raw,$pos2,$pos3),$match_mp);
  preg_match('/([0-9]+)( \[([-0-9]+)\])?/',substr($raw,$pos3,$pos4),$match_act); 
  preg_match('/([0-9]+)( \[([-0-9]+)\])?/',substr($raw,$pos4,$pos5),$match_init); 

  $out['hp'] = $match_hp[1];
  if ($match_hp[3]) {
    $att['hp'] = $match_hp[3];
    $out['hp'].= ' ['. $att['hp'] .']';
  } else {
    $att['hp'] = $match_hp[1];
  }

  $out['regen hp'] = $match_hp[4];
  if ($match_hp[6]) {
    $att['rhp'] = $match_hp[6];
    $out['regen hp'].= ' ['. $att['rhp'] .']';
  } else {
    $att['rhp'] = $match_hp[4];
  }

  $out['mp'] = $match_mp[1];
  if ($match_mp[3]) {
    $att['mp'] = $match_mp[3];
    $out['mp'].= ' ['. $att['mp'] .']';
  } else {
    $att['mp'] = $match_mp[1];
  }

  $out['regen mp'] = $match_mp[4];
  if ($match_mp[6]) {
    $att['rmp'] = $match_mp[6];
    $out['regen mp'].= ' ['. $att['rmp'] .']';
  } else {
    $att['rmp'] = $match_mp[4];
  }

  $out['actions'] = $match_act[1]; 
  if ($match_act[3]) {
    $att['act'] = $match_act[3];
    $out['actions'].= ' ['. $att['act'] .']';
  } else {
    $att['act'] = $match_act[1];
  }

  $out['bonus initiative'] = $match_init[1];
  if ($match_init[3]) {
    $att['init'] = $match_init[3];
    $out['bonus initiative'].= ' ['. $att['init'] .']';
  } else {
    $att['init'] = $match_init[1];
  }

  $pos6 = strpos($raw,'Armure',$pos5);
  $pos7 = strpos($raw,'Armure',$pos6 + 6);
  //$pos8 = strpos($raw,"Indications",$pos7);
  $pos8 = strpos($raw,'[Cacher]', $pos7);
  if (!$pos8) $pos8 = strpos($raw,'[Afficher]', $pos7);
  preg_match_all("/\r\n?([^\t\r]*)\t([^\t\r]*)\t([^\t\r]*)/",substr($raw,$pos7,$pos8-$pos7),$match);
  foreach ($match[1] as $n=>$m) {
    if (trim($match[3][$n])) {
      $match[2][$n] = preg_replace(array('/combat rapproché \(z\)/','/combat à distance \(z\)/','/sortilège \(z\)/'), array('cac','cad','mag'), $match[2][$n]);
      $armor[] = array($match[1][$n],$match[2][$n],$match[3][$n]);
    }
  }
//var_dump($armor);

  $out['total initiative'] = (2*$att['vit']) + $att['per'] + $att['init'];

  /*$reste = substr($raw,$pos8);
  $pos9 = strpos($reste,$name);

  if ($pos9) {
    preg_match("/([^\t\r]*)\r\n([^\t\r]*)\r\nNiv\. /",substr($reste,$pos9),$match);
    $class = $out['class'] = $match[1];
    $race = $out['race'] = $match[2];
  }*/
}

// Bonus Equip
$list = array('cac', 'cad', 'mag', 'soc', 'fdn', 'emb', 'pie', 'mal', 'exp', 'des', 'eff', 'pre', 'imm');
$list_noms = array('CaC', 'CaD', 'Magie', 'Social', 'FdN', 'Embuscade', 'Pièges', 'Maladie', 'Explosion', 'Désorientation', 'Effroi', 'Tir de précision', 'Immobilisation');
$bonus_eq;
for ($i = 0; $i < count($list); $i++) {
	if (isset($_POST['p'.$list[$i].'_bf']) && (int)$_POST['p'.$list[$i].'_bf'] != 0) {
		$bonus_eq['f']['def'][$list[$i]] = $_POST['p'.$list[$i].'_bf'];
	} else {
		$bonus_eq['f']['def'][$list[$i]] = 0;
	}
	
	if (isset($_POST['p'.$list[$i].'_p']) && (float)$_POST['p'.$list[$i].'_p'] != 0) {
		$bonus_eq['p']['def'][$list[$i]] = $_POST['p'.$list[$i].'_p'];
	} else {
		$bonus_eq['p']['def'][$list[$i]] = 1;
	}
	
	if (isset($_POST['a'.$list[$i].'_bf']) && (int)$_POST['a'.$list[$i].'_bf'] != 0) {
		$bonus_eq['f']['att'][$list[$i]] = $_POST['a'.$list[$i].'_bf'];
	} else {
		$bonus_eq['f']['att'][$list[$i]] = 0;
	}
	
	if (isset($_POST['a'.$list[$i].'_p']) && (float)$_POST['a'.$list[$i].'_p'] != 0) {
		$bonus_eq['p']['att'][$list[$i]] = $_POST['a'.$list[$i].'_p'];
	} else {
		$bonus_eq['p']['att'][$list[$i]] = 1;
	}
}
/*echo "<pre>";
print_r($_POST);
print_r($bonus_eq);
echo "</pre>";*/

if (isset($_POST['talents']) and !empty($_POST['talents'])) {
  
  $race_stats['Rashani'] = array(
    'att' => array( 'melee'    => 2 + ($niv * .25), 'distance' => 1 + ($niv * .2), 'magie'    => -2 - ($niv * .25), 'social'   => 0),
    'def' => array( 'melee'    => 0, 'distance' =>  0, 'magie'    => 1 + ($niv * .2), 'social'   => -2 - ($niv * .25)));

  $race_stats['Semi-homme'] = array(
    'att' => array( 'melee'    => -2 - ($niv * .25), 'distance' => -1 - ($niv * .2), 'magie'    =>  0, 'social'   =>  2 + ($niv * .25)),
    'def' => array( 'melee'    =>  0, 'distance' =>  2 + ($niv * .25), 'magie'    =>  1 + ($niv * .2), 'social'   => -2 - ($niv * .25)));

  $race_stats['Dinturan'] = array(
    'att' => array( 'melee'    =>  3 + ($niv * .34), 'distance' =>  0, 'magie'    =>  0, 'social'   =>  1 + ($niv * .20)),
    'def' => array( 'melee'    =>  0, 'distance' =>  0, 'magie'    => -4 - ($niv * .5), 'social'   =>  0));

  $race_stats['Elfe Mag-Mor'] = array(
    'att' => array( 'melee'    =>  1 + ($niv * .2), 'distance' =>  1 + ($niv * .2), 'magie'    =>  0, 'social'   => -2 - ($niv * .25)),
    'def' => array( 'melee'    =>  0, 'distance' =>  0, 'magie'    =>  0, 'social'   =>  0));

  $race_stats['Elfe Tirem-Ag'] = array(
    'att' => array( 'melee'    => -1 - ($niv * .2), 'distance' =>  0, 'magie'    =>  1 + ($niv * .2), 'social'   => -2 - ($niv * .34)),
    'def' => array( 'melee'    =>  0, 'distance' =>  0, 'magie'    =>  3 + ($niv * .34), 'social'   =>  0));

  $race_stats['Frontalier'] = array(
    'att' => array( 'melee'    =>  0, 'distance' =>  1 + ($niv * .2), 'magie'    =>  1 + ($niv * .2), 'social'   =>  0),
    'def' => array( 'melee'    =>  0, 'distance' =>  0, 'magie'    =>  0, 'social'   => -2 - ($niv * .25)));

  $race_stats['Gnome'] = array(
    'att' => array( 'melee'    =>  1 + ($niv * .2), 'distance' => -2 - ($niv * .25), 'magie'    =>  2 + ($niv * .25), 'social'   => -3 - ($niv * .34)),
    'def' => array( 'melee'    => -1 - ($niv * .2), 'distance' =>  1 + ($niv * .2), 'magie'    =>  2 + ($niv * .25), 'social'   =>  0));

  $race_stats['Homme des bois'] = array(
    'att' => array( 'melee'    =>  0, 'distance' =>  2 + ($niv * .25), 'magie'    =>  2 + ($niv * .25), 'social'   =>  0),
    'def' => array( 'melee'    =>  0, 'distance' =>  1 + ($niv * .2), 'magie'    => -2 - ($niv * .25), 'social'   => -3 - ($niv * .34)));

  $race_stats['Kérasi'] = array(
    'att' => array( 'melee'    =>  1 + ($niv * .2), 'distance' =>  0, 'magie'    =>  0, 'social'   => -3 - ($niv * .34)),
    'def' => array( 'melee'    =>  1 + ($niv * .2), 'distance' =>  1 + ($niv * .2), 'magie'    => -1 - ($niv * .2), 'social'   => -1 + ($niv * .2)));

  $race_stats['Nain des collines'] = array(
    'att' => array( 'melee'    =>  2 + ($niv * .25), 'distance' => -2 - ($niv * .25), 'magie'    => -1 - ($niv * .2), 'social'   => -2 - ($niv * .25)),
    'def' => array( 'melee'    =>  0, 'distance' =>  1 + ($niv * .2), 'magie'    =>  2 + ($niv * .25), 'social'   =>  0));


  $race_stats['Nain des montagnes'] = array(
    'att' => array( 'melee'    =>  1 + ($niv * .2), 'distance' => -3 - ($niv * .34), 'magie'    =>  2 + ($niv * .25), 'social'   => -3 - ($niv * .34)),
    'def' => array( 'melee'    =>  0, 'distance' =>  1 + ($niv * .2), 'magie'    =>  2 + ($niv * .25), 'social'   =>  0));

  $raw = trim(stripslashes($_POST['talents']));

  $pos = strpos($raw,'Coûts');
  $pos1 = strpos($raw,'Lignes par page',$pos);
  if (!$pos1) $pos1 = strlen($raw);
  preg_match_all('/\r\n[0-9]+[ \t]*([^\n\r\t0-9]*)[\t \r\n]*[^0-9-n]*([0-9]*|-)([\t \r\n]*\[([0-9]*)\])?/',substr($raw,$pos,$pos1),$match);

  foreach ($match[1] as $n=>$val) {
    if (strpos($val,'partir du niveau')) break;
    $sk = $match[4][$n] ? $match[4][$n] : $match[2][$n];
    $sk_value = $match[2][$n];
    if ($match[4][$n]) $sk_value.= ' ['.$match[4][$n].']';
    $sk_name = trim(stripslashes($val));
    if (substr($sk_name,-1,1) == '-') {
      $sk_name = trim(substr($sk_name,0,-1));
      $sk = $sk_value = 0;
    }
    $out[$sk_name] = $sk_value;
    if ($sk_name) {
      $skills[$sk_name] = $sk;
      $order[$sk][] = $sk_name;
    }
  }

  if ($skills['Typique pour un Dinturan !']) {
    $race_stats[$race]['att']['melee'] += $skills['Typique pour un Dinturan !'];
    $race_stats[$race]['att']['social'] += (.34 * $skills['Typique pour un Dinturan !']);
    $race_stats[$race]['def']['magie'] -= 2 * $skills['Typique pour un Dinturan !'];
  } elseif ($skills['Typique pour un Elfe Mag-Mor !']) {
    $race_stats[$race]['att']['melee'] += (.34 * $skills['Typique pour un Elfe Mag-Mor !']);
    $race_stats[$race]['att']['distance'] += (.34 * $skills['Typique pour un Elfe Mag-Mor !']);
    $race_stats[$race]['att']['social'] -= (.5 * $skills['Typique pour un Elfe Mag-Mor !']);
  } elseif ($skills['Typique pour un Elfe Tirem-Ag !']) {
    $race_stats[$race]['att']['melee'] -= (.34 * $skills['Typique pour un Elfe Tirem-Ag !']);
    $race_stats[$race]['att']['social'] -= $skills['Typique pour un Elfe Tirem-Ag !'];
    $race_stats[$race]['att']['magie'] += (.34 * $skills['Typique pour un Elfe Tirem-Ag !']);
    $race_stats[$race]['def']['magie'] += $skills['Typique pour un Elfe Tirem-Ag !'];
  } elseif ($skills['Typique pour un frontalier!']) {
    $race_stats[$race]['att']['magie'] += (.34 * $skills['Typique pour un frontalier!']);
    $race_stats[$race]['att']['distance'] += (.34 * $skills['Typique pour un frontalier!']);
    $race_stats[$race]['def']['social'] -= (.5 * $skills['Typique pour un frontalier!']);
  } elseif ($skills['Typique pour un Gnome !']) {
    $race_stats[$race]['att']['social'] -= $skills['Typique pour un Gnome !'];
    $race_stats[$race]['att']['distance'] -= (.5 * $skills['Typique pour un Gnome !']);
    $race_stats[$race]['att']['magie'] += (.5 * $skills['Typique pour un Gnome !']);
    $race_stats[$race]['att']['melee'] += (.34 * $skills['Typique pour un Gnome !']);
    $race_stats[$race]['def']['melee'] -= (.34 * $skills['Typique pour un Gnome !']);
    $race_stats[$race]['def']['magie'] += (.5 * $skills['Typique pour un Gnome !']);
    $race_stats[$race]['def']['distance'] += (.34 * $skills['Typique pour un Gnome !']);
  } elseif ($skills['Typique pour un Homme des bois !']) {
    $race_stats[$race]['att']['distance'] += (.5 * $skills['Typique pour un Homme des bois !']);
    $race_stats[$race]['att']['magie'] += (.5 * $skills['Typique pour un Homme des bois !']);
    $race_stats[$race]['def']['magie'] -= (.5 * $skills['Typique pour un Homme des bois !']);
    $race_stats[$race]['def']['social'] -= $skills['Typique pour un Homme des bois !'];
    $race_stats[$race]['def']['distance'] += (.34 * $skills['Typique pour un Homme des bois !']);
  } elseif ($skills['Typique pour un Kérasi !']) {
    $race_stats[$race]['att']['melee'] += (.34 * $skills['Typique pour un Kérasi !']);
    $race_stats[$race]['att']['social'] -= $skills['Typique pour un Kérasi !'];
    $race_stats[$race]['def']['melee'] += (.34 * $skills['Typique pour un Kérasi !']);
    $race_stats[$race]['def']['distance'] += (.34 * $skills['Typique pour un Kérasi !']);
    $race_stats[$race]['def']['social'] += (.34 * $skills['Typique pour un Kérasi !']);
    $race_stats[$race]['def']['magie'] -= (.34 * $skills['Typique pour un Kérasi !']);
  } elseif ($skills['Typique pour un Nain des collines !']) {
    $race_stats[$race]['att']['melee'] += (.34 * $skills['Typique pour un Nain des collines !']);
    $race_stats[$race]['att']['distance'] -= (.5 * $skills['Typique pour un Nain des collines !']);
    $race_stats[$race]['att']['social'] -= (.5 * $skills['Typique pour un Nain des collines !']);
    $race_stats[$race]['att']['magie'] -= (.34 * $skills['Typique pour un Nain des collines !']);
    $race_stats[$race]['def']['distance'] += (.34 * $skills['Typique pour un Nain des collines !']);
    $race_stats[$race]['def']['magie'] += (.5 * $skills['Typique pour un Nain des collines !']);
  } elseif ($skills['Typique pour un Nain des montagnes !']) {
    $race_stats[$race]['att']['melee'] += (.34 * $skills['Typique pour un Nain des montagnes !']);
    $race_stats[$race]['att']['magie'] += (.5 * $skills['Typique pour un Nain des montagnes !']);
    $race_stats[$race]['att']['distance'] -= $skills['Typique pour un Nain des montagnes !'];
    $race_stats[$race]['att']['social'] -= $skills['Typique pour un Nain des montagnes !'];
    $race_stats[$race]['def']['magie'] += (.5 * $skills['Typique pour un Nain des montagnes !']);
    $race_stats[$race]['def']['distance'] += (.34 * $skills['Typique pour un Nain des montagnes !']);
  } elseif ($skills['Typique pour un Rashani !']) {
    $race_stats[$race]['att']['melee'] += (.5 * $skills['Typique pour un Rashani !']);
    $race_stats[$race]['att']['distance'] += (.34 * $skills['Typique pour un Rashani !']);
    $race_stats[$race]['att']['magie'] -= (.5 * $skills['Typique pour un Rashani !']);
    $race_stats[$race]['def']['social'] -= (.5 * $skills['Typique pour un Rashani !']);
    $race_stats[$race]['def']['magie'] += (.34 * $skills['Typique pour un Rashani !']);
  } elseif ($skills['Typique pour un Semi-homme !']) {
    $race_stats[$race]['att']['melee'] -= (.5 * $skills['Typique pour un Semi-homme !']);
    $race_stats[$race]['att']['distance'] -= (.34 * $skills['Typique pour un Semi-homme !']);
    $race_stats[$race]['att']['social'] += (.5 * $skills['Typique pour un Semi-homme !']);
    $race_stats[$race]['def']['distance'] += (.5 * $skills['Typique pour un Semi-homme !']);
    $race_stats[$race]['def']['magie'] += (.34 * $skills['Typique pour un Semi-homme !']);
    $race_stats[$race]['def']['social'] -= (.5 * $skills['Typique pour un Semi-homme !']);
  }

  $race_stats = array(
	'def' => array(
		'CaC' 	=> $race_stats[$race]['def']['melee'] 	+ (.75 * $skills['Veinard']),
		'CaD'	=> $race_stats[$race]['def']['distance']+ (.75 * $skills['Veinard']) + (1.25 * $skills['Petite personne']),
		'Mag' 	=> $race_stats[$race]['def']['magie'] 	+ (.75 * $skills['Veinard']) + (.34 * $skills['Têtu']),
		'Soc' 	=> $race_stats[$race]['def']['social'] 	+ (.75 * $skills['Veinard']) + (1.25 * $skills['Entêtement']) + (.25 * $skills['Raconter des histoires']),
		'FdN' 	=> 0 + (.75 * $skills['Veinard']) + (1.25 * $skills['Proche de la nature']),
		'Emb' 	=> 0 + (.75 * $skills['Veinard']) + (1.25 * $skills['Sixième sens']),
		'Piè'	=> 0 + (.75 * $skills['Veinard']) + (1.25 * $skills['Sixième sens']),
		'Mal' 	=> 0 + (.75 * $skills['Veinard']) + (1.25 * $skills['Tenace']),
		'Exp'	=> 0 + (.75 * $skills['Veinard']),
		'Dés'	=> 0 + (.75 * $skills['Veinard']),
		'Eff' 	=> 0 + (.75 * $skills['Veinard']),
		'Pré'	=> 0 + (.75 * $skills['Veinard']) + (1.25 * $skills['Petite personne']),
		'Imm'	=> 0 + (.75 * $skills['Veinard'])
	),
	'att' => array(
		'CaC' 	=> $race_stats[$race]['att']['melee'],
		'CaD'	=> $race_stats[$race]['att']['distance'],
		'Mag'	=> $race_stats[$race]['att']['magie'],
		'Soc' 	=> $race_stats[$race]['att']['social'] + (.25 * $skills['Raconter des histoires']),
		'FdN' 	=> 0,
		'Emb' 	=> 0,
		'Pie'	=> 0,
		'Mal' 	=> 0,
		'Exp'	=> 0,
		'Dés'	=> 0,
		'Eff' 	=> 0,
		'Pré'	=> 0,
		'Imm'	=> 0
	)
  );

  $dons_stats = array(
	'def' => array(
		'CaC' 	=> .5 * $skills['Don : intuition divine'] + ($skills['Don : stabilité']?1 + .5 * $skills['Don : stabilité']:0) + ($skills['Don : Compagnon digne d\'un hun']?2 + $skills['Don : Compagnon digne d\'un hun']:0) + ($skills['Don : duelliste']?1 + .5 * $skills['Don : duelliste']:0) + $skills['Don : glorieux combattant'] + ($skills['Don : bénédiction de Demosan']?1 + $skills['Don : bénédiction de Demosan']:0) + ($skills['Don : Canal divin']?3 + .5 * $skills['Don : Canal divin']:0),
		'CaD' 	=> .5 * $skills['Don : intuition divine'] + .5 * $skills['Don : cible vivante'] + ($skills['Don : Canal divin']?2 + .34 * $skills['Don : Canal divin']:0),
		'Mag' 	=> .5 * $skills['Don : intuition divine'] + ($skills['Don : astronomie']?4 + 2 * $skills['Don : astronomie']:0) + ($skills['Don : sûr de soi']?2 + .5 * $skills['Don : sûr de soi']:0) + ($skills['Don : combattant dans l\'arène']?2 + .5 * $skills['Don : combattant dans l\'arène']:0) + ($skills['Don : Canal divin']?2 + .34 * $skills['Don : Canal divin']:0),
		'Soc' 	=> .5 * $skills['Don : intuition divine'] + ($skills['Don : galant']?2 + .5 * $skills['Don : galant']:0) + ($skills['Don : sûr de soi']?6 + $skills['Don : sûr de soi']:0) + ($skills['Don : combattant dans l\'arène']?2 + .5 * $skills['Don : combattant dans l\'arène']:0) + .75 * $skills['Don : base de la pyramide'] + ($skills['Don : clown']?1 + .75 * $skills['Don : clown']:0) - ($skills['Don : soupe au lait'] ? 2 + .34 * $skills['Don : soupe au lait']:0),
		'FdN' 	=> .5 * $skills['Don : intuition divine'] + $skills['Don : savoir concernant la nature'] + ($skills['Don : bénédiction de Chasun']?4 + .5 * $skills['Don : bénédiction de Chasun']:0),
		'Emb' 	=> .5 * $skills['Don : intuition divine'] + .5 * $skills['Don : mélodie de l\'aigle'],
		'Piè'	=> .5 * $skills['Don : intuition divine'] + .5 * $skills['Don : mélodie de l\'aigle'],
		'Mal' 	=> .5 * $skills['Don : intuition divine'] + ($skills['Don : avoir un caractère fort']?1 + .5 * $skills['Don : avoir un caractère fort']:0) + .75 * $skills['Don : base de la pyramide'],
		'Exp'	=> .5 * $skills['Don : intuition divine'],
		'Dés'	=> .5 * $skills['Don : intuition divine'],
		'Eff' 	=> .5 * $skills['Don : intuition divine'],
		'Pré'	=> .5 * $skills['Don : intuition divine'],
		'Imm'	=> .5 * $skills['Don : intuition divine']
	),
	'att' => array(
		'CaC' 	=> ($skills['Don : soupe au lait'] ? .75 * $skills['Don : soupe au lait']:0) + ($skills['Don : Compagnon digne d\'un hun']?2 + $skills['Don : Compagnon digne d\'un hun']:0) + ($skills['Don : duelliste'] ?1 + .5 * $skills['Don : duelliste']:0) + ($skills['Don : sûr de soi'] ?2 + .75 * $skills['Don : sûr de soi']:0) + ($skills['Don : griffes du tigre'] ? .5 * $skills['Don : griffes du tigre']:0) + ($skills['Don : vétéran du champ de bataille'] ? .75 * $skills['Don : vétéran du champ de bataille']:0) + ($skills['Don : maître des mots de pouvoir'] ?2 + .25 * $skills['Don : maître des mots de pouvoir']:0) + ($skills['Don : glorieux combattant']? $skills['Don : glorieux combattant']:0) + ($skills['Don : roi de la fanfaronnade']? .5 * $skills['Don : roi de la fanfaronnade']:0) + ($skills['Don : maître des lames']? 1 + .25 * $skills['Don : maître des lames']:0) + ($skills['Don : droit champion']? .75 * $skills['Don : droit champion']:0) + ($skills['Don : Chasseur de fantômes']?2 + .25 * $skills['Don : Chasseur de fantômes']:0) + ($skills['Don : Alliance maudite']? 2 + .25 * $skills['Don : Alliance maudite']:0),
		'CaD' 	=> ($skills['Don : précision de tir inégalée']? 2 + .34 * $skills['Don : précision de tir inégalée']:0) + ($skills['Don : bénédiction de la flèche'] ? 1 + .34 * $skills['Don : bénédiction de la flèche']:0) + ($skills['Don : tireur doué'] ? 1 + .34 * $skills['Don : tireur doué']:0) + ($skills['Don : maître de la poudre noire'] ? 2 + .5 * $skills['Don : maître de la poudre noire']:0) + ($skills['Don : sûr de soi'] ?2 + .75 * $skills['Don : sûr de soi']:0) + ($skills['Don : maître de la fronde'] ?1 + $skills['Don : maître de la fronde']:0) + ($skills['Don : maître à la main calme']? 2 + .5 * $skills['Don : maître à la main calme']:0) + ($skills['Don : tireur de duel doué']? 1 + .34 * $skills['Don : tireur de duel doué']:0),
		'Mag' 	=> ($skills['Don : maître des mots de pouvoir'] ?2 + .25 * $skills['Don : maître des mots de pouvoir']:0) + ($skills['Don : Chasseur de fantômes']?1 + .2 * $skills['Don : Chasseur de fantômes']:0) + ($skills['Don : Alliance maudite']? .25 * $skills['Don : Alliance maudite']:0) + ($skills['Don : art de la magie de combat']? 2 + .75 * $skills['Don : art de la magie de combat']:0),
		'Soc' 	=> ($skills['Don : galant'] ? 2 + .5 * $skills['Don : galant']:0) + $skills['Don : provoquer de l\'angoisse'] + .5 * $skills['Don : sourire gagnant'] + ($skills['Don : roi de la fanfaronnade']? .5 * $skills['Don : roi de la fanfaronnade']:0) + ($skills['Don : clown']?1 + .75 * $skills['Don : clown']:0) + ($skills['Don : Verbe divin']? 3 + .34 * $skills['Don : Verbe divin']:0),
		'FdN' 	=> ($skills['Don : maître de la grêle']? .5 * $skills['Don : maître de la grêle']:0),
		'Emb' 	=> ($skills['Don : cachotterie'] ?2 + .34 * $skills['Don : cachotterie']:0) + ($skills['Don : maître de la chasse'] ?1 + .25 * $skills['Don : maître de la chasse']:0),
		'Piè'	=> 0,
		'Mal' 	=> ($skills['Don : Alliance maudite']? 1 + .2 * $skills['Don : Alliance maudite']:0),
		'Exp'	=> 0,
		'Dés'	=> 0,
		'Eff' 	=> ($skills['Don : effrayant']? $skills['Don : effrayant']:0),
		'Pré'	=> 0,
		'Imm'	=> 0
	)
  );

  $class_stats = array(
	'def' => array(
		'CaC' 	=> 0,
		'Mag' 	=> 0 + (.17 * $skills['Fidèle de Rashon']) + (.5 * $skills['Faveur des étoiles']),
		'CaD' 	=> 0,
		'Soc' 	=> 0 + ($skills['Entrée en scène audacieuse']?2 + .5 * $skills['Entrée en scène audacieuse']:0),
		'FdN' 	=> 0 + (.13 * $skills['Fidèle de Rashon']),
		'Emb' 	=> 0 + (.20 * $skills['Fidèle de Rashon']) + (.75 * $skills['Manoeuvre sournoise']) + (.34 * $skills['Appel de la hyène']) + (.34 * $skills['Rire de la hyène']),
		'Piè'	=> 0 + (.20 * $skills['Fidèle de Rashon']) + ($skills['Saut de dernière minute']),
		'Mal' 	=> 0 + (.25 * $skills['Fidèle de Rashon']) + ($skills[' Coriacité de l\'ours']?2 + .5 * $skills[' Coriacité de l\'ours']:0),
		'Exp'	=> 0,
		'Dés'	=> 0 + ($skills['Intuition']?4 * $skills['Intuition']:0) + ($skills['Pistage']?3 * $skills['Pistage']:0) + ($skills['Illumination']?5 * $skills['Illumination']:0) + ($skills['Instinct de survie']?5 * $skills['Instinct de survie']:0) + ($skills['Traque']?3 * $skills['Traque']:0),
		'Eff' 	=> 0 + ($skills['Inflexible']?$skills['Inflexible'] + .25 * $niv:0) + ($skills['Discipline']?$skills['Discipline'] + .25 * $niv:0) + ($skills['Liberté du fou']?1.5 * $skills['Liberté du fou'] + .25 * $niv:0) + ($skills['Entêtement du bœuf']?1.5 * $skills['Entêtement du bœuf'] + .35 * $niv:0) + ($skills['Calme stoïque']?1.15 * $skills['Calme stoïque'] + .25 * $niv:0) + ($skills['Confiance divine']?1.25 * $skills['Confiance divine'] + .3 * $niv:0),
		'Pré'	=> 0 + ($skills['Éviter un tir']),
		'Imm'	=> 0 + ($skills['Éviter un tir'])
	),
	'att' => array(
		'CaC' 	=> 0 + (.5 * $skills['Pugilat']) + ($skills['Maître d\'armes']?1:0) + (.5 * $skills['Maître escrimeur']),
		'CaD' 	=> 0,
		'Mag' 	=> 0,
		'Soc' 	=> 0,
		'FdN' 	=> 0,
		'Emb' 	=> 0 - (.5 * $skills['Nature de l\'ours (compagnon)']) - (.5 * $skills['Nature de l\'ours (lui-même)']) - (.5 * $skills['Nature de l\'ours (groupe)']) - (.5 * $skills['Nature de l\'aigle (compagnon)']) - (.5 * $skills['Nature de l\'aigle (lui-même)']) - (.5 * $skills['Nature de l\'aigle (groupe)']) + $skills['Marche silencieuse'],
		'Piè'	=> 0,
		'Mal' 	=> 0,
		'Exp'	=> 0,
		'Dés'	=> 0,
		'Eff' 	=> 0,
		'Pré'	=> 0,
		'Imm'	=> 0
	)
  );
  
  $bonus = array(
	'def' => array(
		'CaC' 		=> $race_stats['def']['CaC'] + $class_stats['def']['CaC'] + $dons_stats['def']['CaC'] + $bonus_eq['f']['def']['cac'],
		'CaD' 		=> $race_stats['def']['CaD'] + $class_stats['def']['CaD'] + $dons_stats['def']['CaD'] + $bonus_eq['f']['def']['cad'],
		'Magie' 	=> $race_stats['def']['Mag'] + $class_stats['def']['Mag'] + $dons_stats['def']['Mag'] + $bonus_eq['f']['def']['mag'],
		'Social' 	=> $race_stats['def']['Soc'] + $class_stats['def']['Soc'] + $dons_stats['def']['Soc'] + $bonus_eq['f']['def']['soc'],
		'FdN' 		=> $race_stats['def']['FdN'] + $class_stats['def']['FdN'] + $dons_stats['def']['FdN'] + $bonus_eq['f']['def']['fdn'],
		'Embuscade' => $race_stats['def']['Emb'] + $class_stats['def']['Emb'] + $dons_stats['def']['Emb'] + $bonus_eq['f']['def']['emb'],
		'Piège'		=> $race_stats['def']['Piè'] + $class_stats['def']['Piè'] + $dons_stats['def']['Piè'] + $bonus_eq['f']['def']['pie'],
		'Maladie' 	=> $race_stats['def']['Mal'] + $class_stats['def']['Mal'] + $dons_stats['def']['Mal'] + $bonus_eq['f']['def']['mal'],
		'Explosion'	=> $race_stats['def']['Exp'] + $class_stats['def']['Exp'] + $dons_stats['def']['Exp'] + $bonus_eq['f']['def']['exp'],
		'Désorient'	=> $race_stats['def']['Dés'] + $class_stats['def']['Dés'] + $dons_stats['def']['Dés'] + $bonus_eq['f']['def']['des'],
		'Effroi' 	=> $race_stats['def']['Eff'] + $class_stats['def']['Eff'] + $dons_stats['def']['Eff'] + $bonus_eq['f']['def']['eff'],
		'TirPré'	=> $race_stats['def']['Pré'] + $class_stats['def']['Pré'] + $dons_stats['def']['Pré'] + $bonus_eq['f']['def']['pre'],
		'Immobil'	=> $race_stats['def']['Imm'] + $class_stats['def']['Imm'] + $dons_stats['def']['Imm'] + $bonus_eq['f']['def']['imm']
	),
	'att' => array(
		'CaC' 		=> $race_stats['att']['CaC'] + $class_stats['att']['CaC'] + $dons_stats['att']['CaC'] + $bonus_eq['f']['att']['cac'],
		'CaD' 		=> $race_stats['att']['CaD'] + $class_stats['att']['CaD'] + $dons_stats['att']['CaD'] + $bonus_eq['f']['att']['cad'],
		'Magie' 	=> $race_stats['att']['Mag'] + $class_stats['att']['Mag'] + $dons_stats['att']['Mag'] + $bonus_eq['f']['att']['mag'],
		'Social' 	=> $race_stats['att']['Soc'] + $class_stats['att']['Soc'] + $dons_stats['att']['Soc'] + $bonus_eq['f']['att']['soc'],
		'FdN' 		=> $race_stats['att']['FdN'] + $class_stats['att']['FdN'] + $dons_stats['att']['FdN'] + $bonus_eq['f']['att']['fdn'],
		'Embuscade' => $race_stats['att']['Emb'] + $class_stats['att']['Emb'] + $dons_stats['att']['Emb'] + $bonus_eq['f']['att']['emb'],
		'Piège'		=> $race_stats['att']['Piè'] + $class_stats['att']['Piè'] + $dons_stats['att']['Piè'] + $bonus_eq['f']['att']['pie'],
		'Maladie' 	=> $race_stats['att']['Mal'] + $class_stats['att']['Mal'] + $dons_stats['att']['Mal'] + $bonus_eq['f']['att']['mal'],
		'Explosion'	=> $race_stats['att']['Exp'] + $class_stats['att']['Exp'] + $dons_stats['att']['Exp'] + $bonus_eq['f']['att']['exp'],
		'Désorient'	=> $race_stats['att']['Dés'] + $class_stats['att']['Dés'] + $dons_stats['att']['Dés'] + $bonus_eq['f']['att']['des'],
		'Effroi' 	=> $race_stats['att']['Eff'] + $class_stats['att']['Eff'] + $dons_stats['att']['Eff'] + $bonus_eq['f']['att']['eff'],
		'TirPré'	=> $race_stats['att']['Pré'] + $class_stats['att']['Pré'] + $dons_stats['att']['Pré'] + $bonus_eq['f']['att']['pre'],
		'Immobil'	=> $race_stats['att']['Imm'] + $class_stats['att']['Imm'] + $dons_stats['att']['Imm'] + $bonus_eq['f']['att']['imm']
	)
  );

  $stat_skills = array(
	// PARADES ===================================================================================

    'Éviter un coup' 				 => round($bonus_eq['p']['def']['cac'] * (($skills['Éviter un coup'] ? 1.1 : 1) * ( 2 * $att['adr'] + $att['vit'] + 2 * $skills['Éviter un coup']))	+ $bonus['def']['CaC']),
    'Éviter un tir'  				 => round($bonus_eq['p']['def']['cad'] * (($skills['Éviter un tir'] ? 1.2 : 1) * (2 * $att['vit'] + $att['per'] + 2 * $skills['Éviter un tir']))	+ $bonus['def']['CaD']),

    'Résistance à la magie' 		 => round($bonus_eq['p']['def']['mag'] * (($skills['Résistance à la magie'] ? 1.2 : 1) * (2 * $att['vol'] + $att['int'] + 2 * $skills['Résistance à la magie'])) + $bonus['def']['Magie']),
    'Résistance sociale'  			 => round($bonus_eq['p']['def']['soc'] * (2 * $att['vol'] + $att['cha'])																			+ $bonus['def']['Social']),

    'Résistance force de la nature'  => round($bonus_eq['p']['def']['fdn'] * (2 * $att['vol'] + $att['vit']) + $bonus['def']['FdN']),
    'Résistance embuscade'  		 => round($bonus_eq['p']['def']['emb'] * (2 * $att['per'] + $att['int']) + $bonus['def']['Embuscade']),
    'Résistance décl. piège'  		 => round($bonus_eq['p']['def']['pie'] * (2 * $att['per'] + $att['vit']) + $bonus['def']['Piège']),
    'Résistance maladie'  			 => round($bonus_eq['p']['def']['mal'] * (2 * $att['con'] + $att['cha']) + $bonus['def']['Maladie']),
    'Résistance explosion ou souffle'=> round($bonus_eq['p']['def']['exp'] * (2 * $att['vit'] + $att['per']) + $bonus['def']['Explosion']),
    'Résistance désorientation'  	 => round($bonus_eq['p']['def']['des'] * (2 * $att['per'] + $att['int']) + $bonus['def']['Désorient']),
    'Résistance effroi'  			 => round($bonus_eq['p']['def']['eff'] * (2 * $att['vol'] + $att['cha']) + $bonus['def']['Effroi']),
    'Résistance tir de précision' 	 => round($bonus_eq['p']['def']['pre'] * (2 * $att['vit'] + $att['per']) + $bonus['def']['TirPré']),
    'Résistance immobilisation'  	 => round($bonus_eq['p']['def']['imm'] * (2 * $att['vit'] + $att['per']) + $bonus['def']['Immobil']),

    'Parade au bouclier' 			 => round($bonus_eq['p']['def']['cac'] * (1.05 * ( 2 * $att['adr'] + $att['for'] + 2 * $skills['Parade au bouclier'])) 		+ $bonus['def']['CaC']),
    'Parade Combat à l\'épée' 		 => round($bonus_eq['p']['def']['cac'] * (0.75 * ( 2 * $att['adr'] + $att['vit'] + 2 * $skills['Combat à l\'épée'])) 		+ $bonus['def']['CaC']),
    'Parade Combat à la hache' 		 => round($bonus_eq['p']['def']['cac'] * (0.75 * ( 2 * $att['adr'] + $att['for'] + 2 * $skills['Combat à la hache'])) 		+ $bonus['def']['CaC']),
    'Parade Combat à l\'arme de choc'=> round($bonus_eq['p']['def']['cac'] * (0.75 * ( 2 * $att['adr'] + $att['for'] + 2 * $skills['Combat à l\'arme de choc']))+ $bonus['def']['CaC']),
    'Parade Combat à l\'arme d\'hast'=> round($bonus_eq['p']['def']['cac'] * (0.75 * ( 2 * $att['adr'] + $att['vit'] + 2 * $skills['Combat à l\'arme d\'hast']))+ $bonus['def']['CaC']),
    'Parade Combat au couteau' 		 => round($bonus_eq['p']['def']['cac'] * (0.75 * ( 2 * $att['for'] + $att['vit'] + 2 * $skills['Combat au couteau'])) 		+ $bonus['def']['CaC']),
    'Parade Combat à mains nues' 	 => round($bonus_eq['p']['def']['cac'] * (0.7  * ( 2 * $att['adr'] + $att['vit'] + 2 * $skills['Combat à mains nues']))		+ $bonus['def']['CaC']),
    'Parade Combat au bâton' 		 => round($bonus_eq['p']['def']['cac'] * (0.75 * ( 2 * $att['adr'] + $att['vit'] + 2 * $skills['Combat au bâton']))			+ $bonus['def']['CaC']),
    'Parade Escrime' 				 => round($bonus_eq['p']['def']['cac'] * (0.75 * ( 2 * $att['vit'] + $att['adr'] + 2 * $skills['Escrime']))					+ $bonus['def']['CaC']),
    'Parade Main griffue' 			 => round($bonus_eq['p']['def']['cac'] * (0.75 * ( 2 * $att['vit'] + $att['for'] + 2 * $skills['Main griffue'])) 			+ $bonus['def']['CaC']),

	// Sociales
    'Inflexible'  					 => round($bonus_eq['p']['def']['soc'] * (1.05 * (2 * $att['vol'] + $att['int'] + 2 * $skills['Inflexible'])) 			+ $bonus['def']['Social']),
    'Confiance divine'  			 => round($bonus_eq['p']['def']['soc'] * (1.25 * (2 * $att['vol'] + $att['cha'] + 2 * $skills['Confiance divine'])) 	+ $bonus['def']['Social']),
    'Liberté du fou'  				 => round($bonus_eq['p']['def']['soc'] * (1.05 * (2 * $att['vol'] + $att['cha'] + 2 * $skills['Liberté du fou'])) 		+ $bonus['def']['Social']),
    'Entêtement du bœuf'  			 => round($bonus_eq['p']['def']['soc'] * (1.10 * (2 * $att['vol'] + $att['cha'] + 2 * $skills['Entêtement du bœuf']))	+ $bonus['def']['Social']),
    'Calme stoïque'  				 => round($bonus_eq['p']['def']['soc'] * (1.20 * (2 * $att['vol'] + $att['int'] + 2 * $skills['Calme stoïque'])) 		+ $bonus['def']['Social']),
    'Discipline'  					 => round($bonus_eq['p']['def']['soc'] * (1.15 * (2 * $att['vol'] + $att['cha'] + 2 * $skills['Discipline'])) 			+ $bonus['def']['Social']),

	// INIT ========================================================================================

    'Attaque rapide'  				 => 2 * $att['vit'] + $att['per'] + 2 * $skills['Attaque rapide'] + $att['init'],
    'Mana Attaque rapide' 			 => 3,

    'Vigilance'  					 => 2 * $att['per'] + $att['vit'] + 2 * $skills['Vigilance'] + $att['init'],
    'Mana Vigilance' 				 => 3,

    'Pied léger'  					 => 2 * $att['adr'] + $att['vit'] + 2 * $skills['Pied léger'] + $att['init'],
    'Mana Pied léger' 				 => 4,

    'Vue d\'ensemble'  				 => 2 * $att['per'] + $att['int'] + 2 * $skills['Vue d\'ensemble'] + $att['init'],
    'Mana Vue d\'ensemble' 			 => 4,
    
    'Zèle'  						 => 2 * $att['vol'] + $att['vit'] + 2 * $skills['Zèle'] + $att['init'],
    'Mana Zèle' 					 => 4,

    // ATTAQUES ======================================================================================

    // CaD: arc ---------------------------------------------
    'Attaque Tir à l\'arc' 					=> round($bonus_eq['p']['att']['cad'] * (2 * $att['adr'] + $att['vit'] + 2 * $skills['Tir à l\'arc']) 				+ $bonus['att']['CaD']),
    'Effet Tir à l\'arc' 					=> round($att['per']/2 + $att['for']/3 + $skills['Tir à l\'arc']/2 + .5 * $skills['Don : bénédiction de la flèche']),

    'Attaque Tir de précision à l\'arc' 	=> round($bonus_eq['p']['att']['cad'] * (2 * $att['per'] + $att['adr'] + 2 * $skills['Tir de précision à l\'arc']) 		+ $bonus['att']['CaD']),
    'Effet Tir de précision à l\'arc' 		=> round($att['for']/2 + $att['vol']/3 + $skills['Tir de précision à l\'arc']/2 + $skills['Don : précision de tir inégalée'] + .5 * $skills['Don : bénédiction de la flèche']),
    'Infos Tir de précision à l\'arc' 		=> 'dom.cad perf-10%/+44%/+152%',
    'Mana Tir de précision à l\'arc' 		=> 3,

    'Attaque Double tir' 					=> round($bonus_eq['p']['att']['cad'] * (2 * $att['adr'] + $att['per'] + 2 * $skills['Double tir']) 					+ $bonus['att']['CaD']),
    'Effet Double tir' 						=> round(.75 * ($att['per']/2 + $att['for']/3 + $skills['Double tir']/2) + .5 * $skills['Don : bénédiction de la flèche']),
    'Infos Double tir' 						=> '2 cibles.grp',
    'Mana Double tir' 						=> 2,

    'Attaque Pluie de flèches' 				=> round($bonus_eq['p']['att']['cad'] * (2 * $att['adr'] + $att['con'] + 2 * $skills['Pluie de flèches']) 				+ $bonus['att']['CaD']),
    'Effet Pluie de flèches' 				=> round(.4 * ($att['per']/2 + $att['int']/3 + $skills['Pluie de flèches']/2) + .5 * $skills['Don : bénédiction de la flèche']),
    'Infos Pluie de flèches' 				=> 1 + round(.2 * $niv) .' cibles.pos',
    'Mana Pluie de flèches' 				=> 4,

    // CaD: fronde ---------------------------------------------
    'Attaque Tir à la fronde' 				=> round($bonus_eq['p']['att']['cad'] * (2 * $att['per'] + $att['adr'] + 2 * $skills['Tir à la fronde']) 				+ $bonus['att']['CaD']),
    'Effet Tir à la fronde' 				=> round($att['adr']/2 + $att['for']/3 + $skills['Tir à la fronde']/2),

    'Attaque Pierre de David' 				=> round($bonus_eq['p']['att']['cad'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Pierre de David']) 				+ $bonus['att']['CaD']),
    'Effet Pierre de David' 				=> round($att['adr']/2 + $att['vol']/3 + $skills['Pierre de David']/2 + $skills['Don : précision de tir inégalée']),
    'Infos Pierre de David'					=> 'dom.cad cont,perf -10%/+44%/+152%',
    'Mane Pierre de David'					=> 2,

    'Attaque Pierre de Frumol' 				=> round($bonus_eq['p']['att']['cad'] * (.85 * (2 * $att['per'] + $att['int'] + 2 * $skills['Pierre de Frumol'])) 		+ $bonus['att']['CaD']),
    'Effet Pierre de Frumol' 				=> round($att['for']/2 + $att['adr']/3 + $skills['Pierre de Frumol']/2),
    'Infos Pierre de Frumol' 				=> 'dom.cad cont,perf -50%/-25%/+88% / cible Act-1, ini -'. round(1.5 * $skills['Pierre de Frumol']). ', att,def cad,cac -'. (10 + $skills['Pierre de Frumol']).', att mag-'. round(10 + .5 * $skills['Pierre de Frumol']), 
    'Mana Pierre de Frumol' 				=> 7,

    // CaD: arbalète ---------------------------------------------
    'Attaque Tir à l\'arbalète' 			=> round($bonus_eq['p']['att']['cad'] * (2 * $att['adr'] + $att['per'] + 2 * $skills['Tir à l\'arbalète']) 				+ $bonus['att']['CaD']),
    'Effet Tir à l\'arbalète' 				=> round($att['for']/2 + $att['con']/3 + $skills['Tir à l\'arbalète']/2),

    'Attaque Tir de précision à l\'arbalète'=> round($bonus_eq['p']['att']['cad'] * (2 * $att['per'] + $att['adr'] + 2 * $skills['Tir de précision à l\'arbalète']) + $bonus['att']['CaD']),
    'Effet Tir de précision à l\'arbalète' 	=> round($att['for']/2 + $att['vol']/3 + $skills['Tir de précision à l\'arbalète']/2 + $skills['Don : précision de tir inégalée']),
    'Infos Tir de précision à l\'arbalète' 	=> 'dom.cad cont,perf -10%/+44%/+152%',
    'Mana Tir de précision à l\'arbalète' 	=>  3,

    'Attaque Tir transperçant' 				=> round($bonus_eq['p']['att']['cad'] * (2 * $att['adr'] + $att['con'] + 2 * $skills['Tir transperçant']) 				+ $bonus['att']['CaD']),
    'Effet Tir transperçant' 				=> round(.45 * ($att['per']/2 + $att['int']/3 + $skills['Tir transperçant']/2)),
    'Infos Tir transperçant' 				=> round(1 + .17 * $niv) . ' cibles.grp',
    'Mana Tir transperçant' 				=> 4,

	// CaD: artificier -------------------------------------------
    'Attaque Artificier' 					=> round($bonus_eq['p']['att']['cad'] * (2 * $att['per'] + $att['adr'] + 2 * $skills['Artificier']) 					+ $bonus['att']['CaD']),
    'Effet Artificier' 						=> round(.6 * ($att['int']/2 + $att['per']/3 + $skills['Artificier']/2) + .5 * $skills['Don : maître de la poudre noire']),
    'Infos Artificier'						=> round(3 + .17 * $niv).' cibles.pos, dom.cad gla,mana,psy,pois=0',

    'Attaque Tir au pistolet'				=> round($bonus_eq['p']['att']['cad'] * (2 * $att['adr'] + $att['vit'] + 2 * $skills['Tir au pistolet']) 				+ $bonus['att']['CaD']),
    'Effet Tir au pistolet' 				=> round($att['adr']/2 + $att['per']/3 + $skills['Tir au pistolet']/2 + .5 * $skills['Don : maître à la main calme']),

    'Attaque Tir de précision au pistolet' 	=> round($bonus_eq['p']['att']['cad'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Tir de précision au pistolet'])	+ $bonus['att']['CaD']),
    'Effet Tir de précision au pistolet' 	=> round($att['int']/2 + $att['vol']/3 + $skills['Tir de précision au pistolet']/2),
    'Infos Tir de précision au pistolet' 	=> 'dom.cad cont,perf -10%/+44%/+152%',
    'Mana Tir de précision au pistolet' 	=> '=1',

    'Attaque Pluie de balles' 				=> round($bonus_eq['p']['att']['cad'] * (2 * $att['per'] + $att['vit'] + 2 * $skills['Pluie de balles']) 				+ $bonus['att']['CaD']),
    'Effet Pluie de balles' 				=> round($att['int']/2 + $att['per']/3 + $skills['Pluie de balles']/2),
    'Infos Pluie de balles' 				=> round(2 + .06 * $niv). ' cibles.pos, dom.cad cont,perf -40%/+20%/+50%, dom.cad gla,mana,psy,pois=0',
    'Mana Pluie de balles'			 		=> 2,

	// CaD: sarbacanne -------------------------------------------
	'Tir à la sarbacane'					=> round($bonus_eq['p']['att']['pre'] * (.8 * (2 * $att['per'] + $att['adr'] + 2 * $skills['Tir à la sarbacane'])) 		+ $bonus['att']['TirPré']),

	'Tir à répétition'						=> round($bonus_eq['p']['att']['pre'] * (.8 * (2 * $att['per'] + $att['vit'] + 2 * $skills['Tir à répétition'])) 		+ $bonus['att']['TirPré']),
	'Infos Tir à répétition'				=> round(1 + .13 * $niv).' cibles.grp',
	'Mana Tir à répétition'					=> 3,

    // CaD: autres ---------------------------------------------
    'Attaque Armes de jet' 					=> round($bonus_eq['p']['att']['cad'] * (2 * $att['vit'] + $att['adr'] + 2 * $skills['Armes de jet']) 					+ $bonus['att']['CaD']),
    'Effet Armes de jet' 					=> round($att['per']/2 + $att['for']/3 + $skills['Armes de jet']/2 + .75 * $skills['Don : précision mortelle (armes de jet)']),

    'Lancer de filet' 						=> round($bonus_eq['p']['att']['imm'] * (.85 * (2 * $att['adr'] + $att['vit'] + 2 * $skills['Lancer de filet'])) 		+ $bonus['att']['Immobil']),

	'Marquer sa proie' 						=> round($bonus_eq['p']['att']['cad'] * (1.5* (2 * $att['per'] + $att['int'] + 2 * $skills['Marquer sa proie'])) 		+ $bonus['att']['CaD']),
	'Infos Marquer sa proie'				=> round(2 + .34 * $niv).' cibles.pos, 3T def cad,préc -'.(2 * $skills['Marquer sa proie']),
	'Mana Marquer sa proie'					=> 4,

    // CaC --------------------------------------------------
    'Attaque Coup circulaire' 				=> round($bonus_eq['p']['att']['cac'] * (2 * $att['adr'] + $att['vit'] + 2 * $skills['Coup circulaire']) 				+ $bonus['att']['CaC']),
    'Effet Coup circulaire' 				=> round(.55 * ($att['for']/2 + $att['vol']/3 + $skills['Coup circulaire']/2)),
    'Infos Coup circulaire' 				=> round(.25 * $niv). ' cibles.pos',
    'Mana Coup circulaire' 					=> 2,

    'Attaque Double touche' 				=> round($bonus_eq['p']['att']['cac'] * (2 * $att['adr'] + $att['vit'] + 2 * $skills['Double touche']) 					+ $bonus['att']['CaC']),
    'Effet Double touche' 					=> round(.67 * ($att['for']/2 + $att['vit']/3 + $skills['Double touche']/2) + .34 * $skills['Sous un drapeau noir'] + .34 * $skills['Au nom du roi']),
    'Infos Double touche' 					=> '2 cibles.pos, 1T def cac -'. round(.5 * $skills['Double touche']),
    'Mana Double touche' 					=> '=1',

    'Attaque Coup d\'estoc' 				=> round($bonus_eq['p']['att']['cac'] * (2 * $att['vit'] + $att['adr'] + 2 * $skills['Coup d\'estoc']) 					+ $bonus['att']['CaC']),
    'Effet Coup d\'estoc' 					=> round($att['per']/2 + $att['int']/3 + $skills['Coup d\'estoc']/2 + .34 * $skills['Sous un drapeau noir'] + .34 * $skills['Au nom du roi']),
    'Infos Coup d\'estoc' 					=> 'dom.cac tran,perf -20%/+20%/+110% ',
    'Mana Coup d\'estoc' 					=> 2,

    'Attaque Poignarder' 					=> round($bonus_eq['p']['att']['cac'] * (2 * $att['adr'] + $att['per'] + 2 * $skills['Poignarder']) 					+ $bonus['att']['CaC']),
    'Effet Poignarder' 						=> round(.45 * ($att['vit']/2 + $att['for']/3 + $skills['Poignarder']/2) + .25 * $skills['Art du combat au couteau']),
    'Infos Poignarder' 						=> round(.25 * $niv).' cibles.pos',
    'Mana Poignarder' 						=> 2,

    'Attaque perturbante' 					=> round($bonus_eq['p']['att']['cac'] * (2 * $att['vit'] + $att['int'] + 2 * $skills['Attaque perturbante'])	 		+ $bonus['att']['CaC']),
    'Infos Attaque perturbante' 				=> round(1 + .34 * $niv).'cibles.pos, 2T att cac -'.(2 * $skills['Attaque perturbante']),
    'Mana Attaque perturbante'				=> 2,

    'Attaque Combat à l\'épée' 				=> round($bonus_eq['p']['att']['cac'] * (2 * $att['adr'] + $att['vit'] + 2 * $skills['Combat à l\'épée']) 				+ $bonus['att']['CaC']),
    'Effet Combat à l\'épée' 				=> round($att['for']/2 + $att['adr']/3 + $skills['Combat à l\'épée']/2),

    'Attaque Combat à la hache' 			=> round($bonus_eq['p']['att']['cac'] * (2 * $att['adr'] + $att['for'] + 2 * $skills['Combat à la hache']) 				+ $bonus['att']['CaC']),
    'Effet Combat à la hache' 				=> round($att['for']/2 + $att['per']/3 + $skills['Combat à la hache']/2),

    'Attaque Combat à l\'arme de choc' 		=> round($bonus_eq['p']['att']['cac'] * (2 * $att['adr'] + $att['for'] + 2 * $skills['Combat à l\'arme de choc']) 		+ $bonus['att']['CaC']),
    'Effet Combat à l\'arme de choc' 		=> round($att['for']/2 + $att['con']/3 + $skills['Combat à l\'arme de choc']/2),

    'Attaque Combat au couteau' 			=> round($bonus_eq['p']['att']['cac'] * (2 * $att['vit'] + $att['per'] + 2 * $skills['Combat au couteau']) 				+ $bonus['att']['CaC']),
    'Effet Combat au couteau' 				=> round($att['for']/2 + $att['vit']/3 + $skills['Combat au couteau']/2 + .5 * $skills['Art du scalpel'] + .25 * $skills['Art du combat au couteau']),

    'Attaque Combat à mains nues' 			=> round($bonus_eq['p']['att']['cac'] * (2 * $att['adr'] + $att['vit'] + 2 * $skills['Combat à mains nues']) 			+ $bonus['att']['CaC']),
    'Effet Combat à mains nues' 			=> round($att['for']/2 + $att['con']/3 + $skills['Combat à mains nues']/2 + .5 * $skills['Poing de fer'] + .5 * $skills['Lutte']),

    'Attaque Combat au bâton' 				=> round($bonus_eq['p']['att']['cac'] * (2 * $att['adr'] + $att['vit'] + 2 * $skills['Combat au bâton']) 				+ $bonus['att']['CaC']),
    'Effet Combat au bâton' 				=> round($att['for']/2 + $att['con']/3 + $skills['Combat au bâton']/2),

    'Attaque Escrime' 						=> round($bonus_eq['p']['att']['cac'] * (2 * $att['vit'] + $att['adr'] + 2 * $skills['Escrime']) 						+ $bonus['att']['CaC']),
    'Effet Escrime' 						=> round($att['for']/2 + $att['adr']/3 + $skills['Escrime']/2 + .34 * $skills['Sous un drapeau noir'] + .34 * $skills['Au nom du roi']),

    'Attaque Combat à l\'arme d\'hast' 		=> round($bonus_eq['p']['att']['cac'] * (2 * $att['adr'] + $att['vit'] + 2 * $skills['Combat à l\'arme d\'hast']) 		+ $bonus['att']['CaC']),
    'Effet Combat à l\'arme d\'hast' 		=> round($att['for']/2 + $att['con']/3 + $skills['Combat à l\'arme d\'hast']/2),

    'Attaque Main griffue' 					=> round($bonus_eq['p']['att']['cac'] * (2 * $att['vit'] + $att['for'] + 2 * $skills['Main griffue']) 					+ $bonus['att']['CaC']),
    'Effet Main griffue' 					=> round($att['int']/2 + $att['con']/3 + $skills['Main griffue']/2 + .67 * $skills['Griffes acérées']),
    'Infos Main griffue' 					=> 'dom.cac tran 0/0/+'.round(.25 * $skills['Main griffue']),

    'Attaque Toucher magique' 				=> round($bonus_eq['p']['att']['cac'] * (2 * $att['adr'] + $att['int'] + 2 * $skills['Toucher magique']) 				+ $bonus['att']['CaC']),
    'Effet Toucher magique' 				=> round(.25 * ($att['vol']/2 + $att['per']/3 + $skills['Toucher magique']/2)),

	'Attaque Double coup'					=> round($bonus_eq['p']['att']['cac'] * (2 * $att['adr'] + $att['vit'] + 2 * $skills['Double coup']) 					+ $bonus['att']['CaC']),
	'Effet Double coup'						=> round(.75 * ($att['for']/2 + $att['vit']/3 + $skills['Double coup']/2)),
	'Mana Double coup'						=> 2,

	'Attaque Poings volants'				=> round($bonus_eq['p']['att']['cac'] * (2 * $att['vit'] + $att['for'] + 2 * $skills['Poings volants']) 				+ $bonus['att']['CaC']),
	'Effet Poings volants'					=> round(.8 * ($att['con']/2 + $att['adr']/3 + $skills['Poings volants']/2) + .25 * $skills['Poing de fer']),
	'Infos Poings volants'					=> round(1 + .1 * $niv).' cibles.pos',
	'Mana Poings volants'					=> 1,

	'Attaque Châtiment'						=> round($bonus_eq['p']['att']['cac'] * (.9 * (2 * $att['adr'] + $att['vol'] + 2 * $skills['Châtiment'])) 				+ $bonus['att']['CaC']),
	'Effet Châtiment'						=> round($att['con']/2 + $att['vol']/3 + $skills['Châtiment']/2),
	'Mana Châtiment'						=> 5,

    // Sortilèges --------------------------------------------------------------

    // Pretre
    'Attaque Feu sacré' 					=> round($bonus_eq['p']['att']['mag'] * (2 * $att['cha'] + $att['per'] + 2 * $skills['Feu sacré']) 						+ $bonus['att']['Magie']),
    'Effet Feu sacré' 						=> round(.8 * ($att['vol']/2 + $att['cha']/3 + $skills['Feu sacré']/2)),
    'Infos Feu sacré' 						=> 'dom.mag saint 0/+'. round(.5 * $skills['Feu sacré']).'/+'. round(.5 * $skills['Feu sacré']).', 2T R.PV -'. round(.75 * $skills['Feu sacré']),
    'Mana Feu sacré' 						=> 6,

    'Attaque Feu infernal' 					=> round($bonus_eq['p']['att']['mag'] * (2 * $att['cha'] + $att['per'] + 2 * $skills['Feu infernal']) 					+ $bonus['att']['Magie']),
    'Effet Feu infernal' 					=> round(.6 * ($att['vol']/2 + $att['int']/3 + $skills['Feu infernal']/2)),
    'Infos Feu infernal' 					=> round(.5 * $niv).' cibles.pos, dom.mag saint 0/+'. round(.5 * $skills['Feu infernal']).'/+'. round(.5 * $skills['Feu infernal']).', cible 2T R.PV -'. round(.75 * $skills['Feu infernal']),
    'Mana Feu infernal' 					=> 12,

    'Attaque Exorcisme' 					=> round(2 * $att['per'] + $att['vol'] + 2 * $skills['Exorcisme'] + 5),
    'Effet Exorcisme' 						=> round($att['vol']/2 + $att['cha']/3 + $skills['Exorcisme']/2 + 5),
    'Mana Exorcisme' 						=> 3,

    'Attaque Grand exorcisme' 				=> round(2 * $att['per'] + $att['vol'] + 2 * $skills['Grand exorcisme'] + 5),
    'Effet Grand exorcisme' 				=> round($att['vol']/2 + $att['cha']/3 + $skills['Grand exorcisme']/2 + 5),
    'Infos Grand exorcisme' 				=> "tt cibles.pos", 
    'Mana Grand exorcisme' 					=> 5,

	'Toucher d\'Akbeth'						=> round($bonus_eq['p']['att']['mag'] * (2 * $att['per'] + $att['vol'] + 2 * $skills['Toucher d\'Akbeth'])				+ $bonus['att']['Magie']),
	'Infos Toucher d\'Akbeth'				=> round(1 + .2 * $niv).' cibles.grp, 4T sens.deg.mag feu,gla,elec +'.round(.5 * $skills['Toucher d\'Akbeth']).'/+'.
											round(.75 * $skills['Toucher d\'Akbeth']).'/+'.round($skills['Toucher d\'Akbeth']),
	'Mana Toucher d\'Akbeth'				=> 8,

	'Jugement d\'Akbeth'					=> round($bonus_eq['p']['att']['mag'] * (1.34 * (2 * $att['vol'] + $att['cha'] + 2 * $skills['Jugement d\'Akbeth']))	+ $bonus['att']['Magie']),
	'Infos Jugement d\'Akbeth'				=> round(1 + .08 * $niv).' cibles.grp, 6T Vol -'.round(.25 * $skills['Jugement d\'Akbeth']).', R.PM -'.
											round(.34 * $skills['Jugement d\'Akbeth']).', att mag -'.round(.5 * $skills['Jugement d\'Akbeth']).
											', def mag -'.round($skills['Jugement d\'Akbeth']),
	'Mana Jugement d\'Akbeth'				=> 7,

    // Mage
    'Attaque Flèche d\'énergie' 			=> round($bonus_eq['p']['att']['mag'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Flèche d\'énergie']) 				+ $bonus['att']['Magie']),
    'Effet Flèche d\'énergie' 				=> round(.95 * ($att['vol']/2 + $att['con']/3 + $skills['Flèche d\'énergie']/2) + ($skills['Don : art de la magie de combat']?1 + .5 * $skills['Don : art de la magie de combat']:0)),
    'Infos Flèche d\'énergie' 				=> 'dom.mag elec +'. round(.5 * $niv).'/+'. round(.5 * $niv).'/+'. round(.5 * $niv),
    'Mana Flèche d\'énergie' 				=> 10,

    'Attaque Orage d\'énergie' 				=> round($bonus_eq['p']['att']['mag'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Orage d\'énergie']) 				+ $bonus['att']['Magie']),
    'Effet Orage d\'énergie' 				=> round(.55 * ($att['vol']/2 + $att['for']/3 + $skills['Orage d\'énergie']/2) + ($skills['Don : art de la magie de combat']?1 + .5 * $skills['Don : art de la magie de combat']:0)),
    'Infos Orage d\'énergie' 				=> round(1 + .25 * $niv). ' cibles.pos, dom.mag elec +'. round(.5 * $niv).'/+'. round(.5 * $niv).'/+'. round(.5 * $niv),
    'Mana Orage d\'énergie' 				=> 12,

    'Attaque Tempête d\'énergie' 			=> round($bonus_eq['p']['att']['mag'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Tempête d\'énergie']) 			+ $bonus['att']['Magie']),
    'Effet Tempête d\'énergie' 				=> round(.65 * ($att['vol']/2 + $att['for']/3 + $skills['Tempête d\'énergie']/2) + ($skills['Don : art de la magie de combat']?1 + .5 * $skills['Don : art de la magie de combat']:0)),
    'Infos Tempête d\'énergie' 				=> round(3 + .25 * $niv). ' cibles.grp, dom.mag elec +'. round(.5 * $niv).'/+'. round(.5 * $niv).'/+'. round(.5 * $niv),
    'Mana Tempête d\'énergie' 				=> 15,

    'Attaque Flèche de feu' 				=> round($bonus_eq['p']['att']['mag'] * (2 * $att['int'] + $att['per'] + 2 * $skills['Flèche de feu']) 					+ $bonus['att']['Magie']),
    'Effet Flèche de feu' 					=> round(.7 * ($att['vit']/2 + $att['con']/3 + $skills['Flèche de feu']/2) + .2 * $skills['Maître du feu']),
    'Infos Flèche de feu' 					=> 'tt dom.mag feu +'. round(.5 * $skills['Flèche de feu']).'/+'. round(.5 * $skills['Flèche de feu']).'/+'. round(.5 * $skills['Flèche de feu']).
                                            ', dom.mag feu +'. round(.25 * $skills['Flèche de feu']).'/+'. round(.25 * $skills['Flèche de feu']).'/+'. round(.25 * $skills['Flèche de feu']).
                                            ', 3T R.PV -'. $skills['Flèche de feu'],
    'Mana Flèche de feu' 					=> 7,

    'Attaque Boule de feu' 					=> round($bonus_eq['p']['att']['mag'] * (2 * $att['int'] + $att['per'] + 2 * $skills['Boule de feu']) 					+ $bonus['att']['Magie']),
    'Effet Boule de feu' 					=> round(.6 * ($att['vol']/2 + $att['int']/3 + $skills['Boule de feu']/2) + .2 * $skills['Maître du feu']),
    'Infos Boule de feu' 					=> round(.25 * $niv). ' cibles.pos, dom.mag feu +'. round(.5 * $niv).'/+'. round(.5 * $niv).'/+'. round(.5 * $niv).', 2T R.PV -'. round(.5 * $skills['Boule de feu']),
    'Mana Boule de feu' 					=> 14,

    'Attaque Pluie de feu' 					=> round($bonus_eq['p']['att']['mag'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Pluie de feu']) 					+ $bonus['att']['Magie']),
    'Effet Pluie de feu' 					=> round(.7 * ($att['vol']/2 + $att['con']/3 + $skills['Pluie de feu']/2) + .2 * $skills['Maître du feu']),
    'Infos Pluie de feu' 					=> round(1 + .25 * $niv). ' cibles.grp, dom.mag feu +'. round(.5 * $niv).'/+'. round(.5 * $niv).'/+'. round(.5 * $niv).', 2T R.PV -'. round(.5 * $skills['Pluie de feu']),
    'Mana Pluie de feu' 					=> 16,

    'Attaque Tir de glace' 					=> round($bonus_eq['p']['att']['mag'] * (2 * $att['int'] + $att['per'] + 2 * $skills['Tir de glace']) 					+ $bonus['att']['Magie']),
    'Effet Tir de glace' 					=> round(.7 * ($att['vol']/2 + $att['con']/3 + $skills['Tir de glace']/2) + .2 * $skills['Maître de la glace']),
    'Infos Tir de glace' 					=> 'tt dom.mag gla +'. round(.5 * $niv).'/+'. round(.5 * $niv).'/+'. round(.5 * $niv).
                                            ', dom.mag gla +'. round(.25 * $skills['Tir de glace']).'/+'. round(.25 * $skills['Tir de glace']).'/+'. round(.25 * $skills['Tir de glace']).
                                            ', 1T ini -'. round(.5 * $niv). ', att soc,cac,cad & def cac,cad -'. round(.5 * $niv + $skills['Tir de glace']) .', T+1 ini -'. $skills['Tir de glace'],
    'Mana Tir de glace' 					=> 8,

    'Attaque Nuage de glace' 				=> round($bonus_eq['p']['att']['mag'] * (2 * $att['int'] + $att['per'] + 2 * $skills['Nuage de glace']) 				+ $bonus['att']['Magie']),
    'Effet Nuage de glace' 					=> round(.4 * ($att['vol']/2 + $att['con']/3 + $skills['Nuage de glace']/2) + .2 * $skills['Maître de la glace']),
    'Infos Nuage de glace' 					=> round(1 + .2 * $niv). ' cibles.pos, dom.mag gla +'. round(.5 * $niv).'/+'. round(.5 * $niv).'/+'. round(.5 * $niv).
                                            ', 2T ini -'. round(.34 * $niv + .75 * $skills['Nuage de glace']). ', att soc,cac,cad & def cac,cad -'. round(.34 * $niv + .75 * $skills['Nuage de glace']),
    'Mana Nuage de glace' 					=> 13,

    'Attaque Pluie de glace' 				=> round($bonus_eq['p']['att']['mag'] * (2 * $att['int'] + $att['per'] + 2 * $skills['Pluie de glace']) 				+ $bonus['att']['Magie']),
    'Effet Pluie de glace' 					=> round(.45 * ($att['vol']/2 + $att['con']/3 + $skills['Pluie de glace']/2) + .2 * $skills['Maître de la glace']),
    'Infos Pluie de glace' 					=> round(1 + .34 * $niv). ' cibles.grp, dom.mag gla +'. round(.5 * $niv).'/+'. round(.5 * $niv).'/+'. round(.5 * $niv).
                                            ', 2T ini -'. round(.25 * $niv + .5 * $skills['Pluie de glace']). ', att soc,cac,cad & def cac,cad -'. round(.25 * $niv + .5 * $skills['Pluie de glace']),
    'Mana Pluie de glace' 					=> 17,

    'Marque magique' 						=> round($bonus_eq['p']['att']['mag'] * (1.5 * (2 * $att['per'] + $att['cha'] + 2 * $skills['Marque magique'])) 		+ $bonus['att']['Magie']),
    'Infos Marque magique' 					=> round(2 + .34 * $niv). ' cibles.pos, 3T def mag -'. (2 * $skills['Marque magique']),
    'Mana Marque magique' 					=> 5,

	'Attaque Sortilège offensif'			=> round($bonus_eq['p']['att']['mag'] * (2 * $att['cha'] + $att['int'] + 2 * $skills['Sortilège offensif']) 			+ $bonus['att']['Magie']),
	'Effet Sortilège offensif'				=> round(.1 * ($att['int']/2 + $att['cha']/3 + $skills['Sortilège offensif']/2)),
	'Infos Sortilège offensif'				=> round(.1 * $niv). ' cibles.pos',
	'Mana Sortilège offensif'				=> 4,

	// Erudit
    'Attaque Paroles de pouvoir' 			=> round($bonus_eq['p']['att']['mag'] * (2 * $att['cha'] + $att['int'] + 2 * $skills['Paroles de pouvoir']) 			+ $bonus['att']['Magie']),
    'Effet Paroles de pouvoir' 				=> round(.1 * ($att['int']/2 + $att['cha']/3 + $skills['Paroles de pouvoir']/2)),
    'Infos Paroles de pouvoir'				=> round(.34 * $niv). ' cibles.pos',
    'Mana Paroles de pouvoir'				=> 6,

    'Attaque Retrait de mana' 				=> round($bonus_eq['p']['att']['mag'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Retrait de mana']) 				+ $bonus['att']['Magie']),
    'Effet Retrait de mana' 				=> round(2 * ($att['vol']/2 + $att['con']/3 + $skills['Retrait de mana']/2)),
    'Infos Retrait de mana'					=> round(1 + .2 * $niv).' cibles.pos, 3T R.PM -'.round(.34 * $niv + .34 * $skills['Retrait de mana']),
    'Mana Retrait de mana'					=> 2,

	// Magie noire
	'Petite magie noire'					=> round($bonus_eq['p']['att']['mag'] * (2 * $att['int'] + $att['per'] + 2 * $skills['Petite magie noire']) 			+ $bonus['att']['Magie']),

	'Grande magie noire'					=> round(2 * $att['int'] + $att['per'] + 2 * $skills['Grande magie noire'] 			+ $bonus['att']['Magie']),
	'Infos Grande magie noire'				=> round(.75 * $niv).' cibles.pos',
	'Mana Grande magie noire'				=> 10,

	// Exotiques -----------------------------------------------------------------

    // Chamane
    'Attaque Grêlon' 						=> round($bonus_eq['p']['att']['fdn'] * (.5 * (2 * $att['vol'] + $att['int'] + 2 * $skills['Grêlon']))					+ $bonus['att']['FdN']),
    'Effet Grêlon' 							=> round($att['for']/2 + $att['cha']/3 + $skills['Grêlon']/2),
    'Infos Grêlon'							=> 'dom.fdn gla -10% +'. round(.25 * $niv).'/+20% +'. round(.4 * $niv).'/+40% +'. round(.5 * $niv),
    'Mana Grêlon'							=> 6,

    'Attaque Grêle' 						=> round($bonus_eq['p']['att']['fdn'] * (.5 * (2 * $att['vol'] + $att['cha'] + 2 * $skills['Grêle']))					+ $bonus['att']['FdN']),
    'Effet Grêle' 							=> round($att['con']/2 + $att['int']/3 + $skills['Grêle']/2),
    'Infos Grêle'							=> round(1 + .2 * $niv). ' cibles.pos, dom.fdn gla -20% +'. round(.25 * $niv).'/+10% +'. round(.34 * $niv).'/+30% +'. round(.5 * $niv),
    'Mana Grêle'							=> 8,

	// chaman
	'Appel de la hyène'						=> round($bonus_eq['p']['att']['eff'] * (2 * $att['cha'] + $att['vol'] + 2 * $skills['Appel de la hyène'])				+ $bonus['att']['Effroi']),
	'Infos Appel de la hyène'				=> round(1 + .1 * $niv).' cibles.pos, 2T R.PV,R.PM -'.round(2 + .5 * $skills['Appel de la hyène']).', att cac -'.round(2 + $skills['Appel de la hyène']),
	'Mana Appel de la hyène'				=> 4,

	'Rire de la hyène'						=> round($bonus_eq['p']['att']['eff'] * (2 * $att['cha'] + $att['con'] + 2 * $skills['Rire de la hyène'])				+ $bonus['att']['Effroi']),
	'Infos Rire de la hyène'				=> round(1 + .15 * $niv).' cibles.grp, 2T Vit +'.round(.25 * $skills['Rire de la hyène']).', R.PV,R.PM -'.round(2 + .5 * $skills['Rire de la hyène']).
											', att cac -'.round(1 + .75 * $skills['Rire de la hyène']).', T+1 ini -'.round(.75 * $skills['Rire de la hyène']).', cible act -'.round(.12 * $skills['Rire de la hyène']),
	'Mana Rire de la hyène'					=> 7,

	// prêtre
	'Attaque Malédiction de Rashon'			=> round($bonus_eq['p']['att']['mal'] * (.5 * (2 * $att['con'] + $att['vol'] + 2 * $skills['Malédiction de Rashon']))	+ $bonus['att']['Maladie']),
	'Effet Malédiction de Rashon'			=> round(.2 * ($att['cha']/2 + $att['con']/3 + $skills['Malédiction de Rashon']/2)),
	'Infos Malédiction de Rashon'			=> round(1 + .17 * $niv). ' cibles.pos, 3T Con -'. round(.25 * $skills['Malédiction de Rashon']).', cible act -'. round(.08 * $skills['Malédiction de Rashon']).
											', R.PV -'.round(.5 * $skills['Malédiction de Rashon']).', R.PM -'.round(.34 * $skills['Malédiction de Rashon']),
	'Mana Malédiction de Rashon'			=> 4,

	// Homme des bois
	'Attaque Poignard de terre' 			=> round($bonus_eq['p']['att']['fdn'] * (.75 * .9 * (2 * $att['vol'] + $att['per'] + 2 * $skills['Poignard de terre']))	+ $bonus['att']['FdN']),
    'Effet Poignard de terre' 				=> round($att['int']/2 + $att['cha']/3 + $skills['Poignard de terre']/2),
    'Infos Poignard de terre'				=> '2T Adr, Vit -'. round(.34 * $skills['Poignard de terre']).', att cac,cad & def cac,cad -'. round(.75 * $skills['Poignard de terre']),
    'Mana Poignard de terre'				=> 4,

    // Social -------------------------------------------------------------------

    'Hurlement guerrier' 					=> round($bonus_eq['p']['att']['soc'] * (2 * $att['for'] + $att['cha'] + 2 * $skills['Hurlement guerrier']) 			+ $bonus['att']['Social']),
    'Infos Hurlement guerrier' 				=> round(.34 * $niv). ' cibles.pos, 3T ini -'. 2* $skills['Hurlement guerrier'].', att cac'.
											' T1 -'.round(3 * .75 * $skills['Hurlement guerrier']).' T2 -'.round(2 * .75 * $skills['Hurlement guerrier']).' T3 -'.round(1 * .75 * $skills['Hurlement guerrier']),
    'Mana Hurlement guerrier' 				=> 3,

    'Se moquer' 							=> round($bonus_eq['p']['att']['soc'] * (2 * $att['cha'] + $att['for'] + 2 * $skills['Se moquer']) 						+ $bonus['att']['Social']),
    'Infos Se moquer'						=> '1 cible, 2T For,Adr,Vit -'.round(.34 * $skills['Se moquer']).', att & def cac -'.round(.5 * $skills['Se moquer']),
    'Mana Se moquer'						=> 2,
    
    'Chant de dérision' 					=> round($bonus_eq['p']['att']['soc'] * (2 * $att['cha'] + $att['vol'] + 2 * $skills['Chant de dérision']) 				+ $bonus['att']['Social']),
    'Infos Chant de dérision'				=> round(.25 * $niv).' cibles.pos',
    'Mana Chant de dérision'				=> 3,
    
    'Grand chant de dérision' 				=> round($bonus_eq['p']['att']['soc'] * (2 * $att['cha'] + $att['vol'] + 2 * $skills['Grand chant de dérision']) 		+ $bonus['att']['Social']),
    'Infos Grand chant de dérision'			=> round(1 + .2 * $niv).' cibles.grp',
    'Mana Grand chant de dérision'			=> 6,
    
    'Personnage imposant' 					=> round($bonus_eq['p']['att']['soc'] * (1.5 * (2 * $att['for'] + $att['cha'] + 2 * $skills['Personnage imposant'])) 	+ $bonus['att']['Social']),
    'Infos Personnage imposant'				=> round(1 + .1 * $niv).' cibles.pos, 2T ini -'.round(2 + .5 * $skills['Personnage imposant']).', att & def cac -'.($skills['Personnage imposant']),
    'Mana Personnage imposant'				=> '=1',

    'Apparence impressionnante'				=> round($bonus_eq['p']['att']['soc'] * (2 * $att['cha'] + $att['con'] + 2 * $skills['Apparence impressionnante']) 		+ $bonus['att']['Social']),
    'Infos Apparence impressionnante'		=> round(1 + .1 * $niv).' cibles.pos, 2T ini -'.($skills['Apparence impressionnante']).', Vol -'.round(1 + .2 * $skills['Apparence impressionnante']).
											', att cac -'.round(1.25 * $skills['Apparence impressionnante']).', att cad -'.round(.75 * $skills['Apparence impressionnante']).
											', att mag -'.round($skills['Apparence impressionnante']).', def cac -'.round($skills['Apparence impressionnante']),
    'Mana Apparence impressionnante'		=> 3,

    'Masque illusoire' 						=> round($bonus_eq['p']['att']['soc'] * (2 * $att['cha'] + $att['vol'] + 2 * $skills['Masque illusoire']) 				+ $bonus['att']['Social']),
    'Infos Masque illusoire' 				=> 'tt, 2T att cad,cac,mag -'. (round(.5 * $skills['Masque illusoire'] + .25 * $niv)),
    'Mana Masque illusoire'  				=> 2,

    'Regard sévère' 						=> round($bonus_eq['p']['att']['soc'] * (2 * $att['per'] + $att['cha'] + 2 * $skills['Regard sévère']) 					+ $bonus['att']['Social']),
    'Infos Regard sévère'					=> '1 cible, 2T cible act -'.round(1 + .05 * $skills['Regard sévère']),
    'Mana Regard sévère'					=> 4,
    
    'Faire des grimaces' 					=> round($bonus_eq['p']['att']['soc'] * (2 * $att['cha'] + $att['per'] + 2 * $skills['Faire des grimaces']) 			+ $bonus['att']['Social']),
    'Infos Faire des grimaces'				=> round(1 + .25 * $niv).' cibles.pos, 1T def cac,cad -'.round(1.5 * $skills['Faire des grimaces']),
    'Mana Faire des grimaces'				=> 2,
    
    'Intimider' 							=> round($bonus_eq['p']['att']['soc'] * (2 * $att['con'] + $att['cha'] + 2 * $skills['Intimider']) 						+ $bonus['att']['Social']),
    'Infos Intimider'						=> round(2 + .13 * $iv).' cibles.pos, T1 att cac -'.round(6 + (2 * .34 * $skills['Intimider'])).', def cac -'.round(4 * .75 * $skills['Intimider']).' / T2 att cac -'.round(3 + .34 * $skills['Intimider']).
											', def cac -'.round(3 * .75 * $skills['Intimider']).' / T3 def cac -'.round(2 * .75 * $skills['Intimider']).' / T4 def cac -'.round(.75 * $skills['Intimider']),
	'Mana Intimider'						=> 3,

    'Entrée en scène audacieuse' 			=> round($bonus_eq['p']['att']['soc'] * (2 * $att['cha'] + $att['con'] + 2 * $skills['Entrée en scène audacieuse']) 	+ $bonus['att']['Social']),
    'Infos Entrée en scène audacieuse' 		=> round(1 + .2 * $niv).' cibles.grp, 2T ini -'. round(3 + .34 * $skills['Entrée en scène audacieuse']).', att cac -'. $skills['Entrée en scène audacieuse'],
    'Mana Entrée en scène audacieuse' 		=> '=1',

    'Bonne réputation' 						=> round($bonus_eq['p']['att']['soc'] * (2 * $att['cha'] + $att['con'] + 2 * $skills['Bonne réputation']) 				+ $bonus['att']['Social']),
    'Infos Bonne réputation' 				=> round(1 + .2 * $niv).' cibles.grp, 3T ini -'. round(2 + .34 * $skills['Bonne réputation']).', vit -'. round(2 + .17 * $skills['Bonne réputation']).', 4T att cac -'. round(.75 * $skills['Bonne réputation']),
    'Mana Bonne réputation' 				=> '=1',

	'Regard du cobra'						=> round($bonus_eq['p']['att']['soc'] * (2 * $att['int'] + $att['cha'] + 2 * $skills['Regard du cobra']) 				+ $bonus['att']['Social']),
	'Infos Regard du cobra'					=> round(1 + .08 * $niv).' cibles.pos, T1 ini -'.round(1.5 * $skills['Regard du cobra']).', att cac,cad,mag -'.round($skills['Regard du cobra']).
											', 2T (T+1) ini -'.round($skills['Regard du cobra']).', att cac,cad,mag -'. round(.5 * $skills['Regard du cobra']).', T+1 cible act -'.
											round(1 + .05 * $skills['Regard du cobra']).', 2T(T+2) cible act -'.round(.05 * $skills['Regard du cobra']),
	'Mana Regard du cobra'					=> 7,

	'Condamner'								=> round($bonus_eq['p']['att']['soc'] * (2 * $att['vol'] + $att['int'] + 2 * $skills['Condamner']) 						+ $bonus['att']['Social']),
	'Infos Condamner'						=> '2T dom.dég.cac cont,tran,perf +'.round(.75 * $skills['Condamner']).'/+'.round(.5 * $skills['Condamner']).'/+'.round(.25 * $skills['Condamner']).
											', sens.deg saint +'.round($skills['Condamner']).'/+'.round($skills['Condamner']).'/+'.round($skills['Condamner']),
	'Mana Condamner'						=> 2,

	// autres --------------------------------------------------------------

	// Embuscades
    'Embuscade' 							=> round($bonus_eq['p']['att']['emb'] * (2 * $att['per'] + $att['vit'] + 2 * $skills['Embuscade']) 						+ $bonus['att']['Embuscade']),

    'Manoeuvre sournoise' 					=> round($bonus_eq['p']['att']['emb'] * (2 * $att['adr'] + $att['vit'] + 2 * $skills['Manoeuvre sournoise'])			+ $bonus['att']['Embuscade']),

	'Désarmement'							=> round($bonus_eq['p']['att']['cac'] * (.75 * (2 * $att['adr'] + $att['int'] + 2 * $skills['Désarmement'])) 			+ $bonus['att']['CaC']),
	'Infos Désarmement'						=> '2T def.dom.cac con,tran,perf -'.$skills['Désarmement'].'/-'.$skills['Désarmement'].'/-'.$skills['Désarmement'],
	'Mana Désarmement'						=> '=1',

	// Orientation
	'Attaque Pistage'						=> round($bonus_eq['p']['att']['des'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Pistage'])						+ $bonus['att']['Désorient']),
	'Effet Pistage'							=> round($att['int']/2 + $att['vol']/3 + $skills['Pistage']/2),

	'Attaque Illumination'					=> round($bonus_eq['p']['att']['des'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Illumination'])					+ $bonus['att']['Désorient']),
	'Effet Illumination'					=> round($att['int']/2 + $att['vol']/3 + $skills['Illumination']/2),

	'Attaque Intuition'						=> round($bonus_eq['p']['att']['des'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Intuition'])						+ $bonus['att']['Désorient']),
	'Effet Intuition'						=> round($att['int']/2 + $att['vol']/3 + $skills['Intuition']/2),

	'Attaque Instinct de survie'			=> round($bonus_eq['p']['att']['des'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Instinct de survie'])				+ $bonus['att']['Désorient']),
	'Effet Instinct de survie'				=> round($att['int']/2 + $att['vol']/3 + $skills['Instinct de survie']/2),

	'Attaque Traque'						=> round($bonus_eq['p']['att']['des'] * (2 * $att['per'] + $att['int'] + 2 * $skills['Traque'])							+ $bonus['att']['Désorient']),
	'Effet Traque'							=> round($att['int']/2 + $att['vol']/3 + $skills['Traque']/2),

	// Pièges
    'Attaque Désamorcer des pièges' 		=> round($bonus_eq['p']['att']['pie'] * (2 * $att['per'] + $att['adr'] + 2 * $skills['Désamorcer des pièges'])			+ $bonus['att']['Piège']),
    'Effet Désamorcer des pièges' 			=> round($att['int']/2 + $att['vit']/3 + $skills['Désamorcer des pièges']/2),
    'Mana Désamorcer des pièges'			=> 2,

    // dons ---------------------------------------------------------------

    'Attaque Don : explosif' 				=> round($bonus_eq['p']['att']['exp'] * (0.5 * (2 * $att['per'] + $att['int'] + 2 * $skills['Don : explosif']))			+ $bonus['att']['Explosion']),
    'Effet Don : explosif' 					=> round($att['vol']/2 + $att['adr']/3 + $skills['Don : explosif']/2),
    'Infos Don : explosif' 					=> round(1 + .12 * $niv). ' cibles.pos, Eff.niv R.PV -'.round(1 + .25 * $skills['Don : explosif']).', Per -'.$skills['Don : explosif'].', att cac,cad -50%',
    'Mana Don : explosif'					=> 1,

    'Attaque Don : utilisation alternative de poudre noire' =>  round($bonus_eq['p']['att']['exp'] * (0.5 * (2 * $att['int'] + $att['per'] + 2 * $skills['Don : utilisation alternative de poudre noire'])) + $bonus['att']['Explosion']),
    'Effet Don : utilisation alternative de poudre noire' =>    round($att['adr']/2 + $att['vol']/3 + $skills['Don : utilisation alternative de poudre noire']/2),
    'Infos Don : utilisation alternative de poudre noire' =>    round(2 + .13 * $niv). ' cibles.pos, Eff.niv R.PV -'. round(2 * $skills['Don : utilisation alternative de poudre noire']),
    'Mana Don : utilisation alternative de poudre noire'  => 2,

	'Don : inspire le respect'				=> round($bonus_eq['p']['att']['soc'] * (2 * $att['cha'] + $att['con'] + 2 * $skills['Don : inspire le respect']) 		+ $bonus['att']['Social']),
	'Infos Don : inspire le respect'		=> round(.1 * $niv). ' cibles.pos, 7T Adr,Vit -'.round(.25 * $skills['Don : inspire le respect']).', ini -'.round($skills['Don : inspire le respect']).
											', att & def cac -'.round(1 + $skills['Don : inspire le respect']),
	'Mana Don : inspire le respect'			=> 2,

	'Don : cri de perdition'				=> round($bonus_eq['p']['att']['soc'] * (2 * $att['vol'] + $att['int'] + 2 * $skills['Don : cri de perdition']) 		+ $bonus['att']['Social']),
	'Infos Don : cri de perdition'			=> round(.2 * $niv). ' cibles.pos, 4T Int,Cha -'.round(.25 * $skills['Don : cri de perdition']).', att soc -'.round(.5 * $skills['Don : cri de perdition']).
											', talents compos de courage,guérison,dérision & culture -'.round(.34 * $skills['Don : cri de perdition']),
	'Mana Don : cri de perdition'			=> 3,

	'Attaque Don : provoquer de l\'angoisse'=> round($bonus_eq['p']['att']['mag'] * (2 * $att['vol'] + $att['cha'] + 2 * $skills['Don : provoquer de l\'angoisse'])	+ $bonus['att']['Magie']),
    'Effet Don : provoquer de l\'angoisse'	=> round(.1 * ($att['int']/2 + $att['cha']/3 + $skills['Don : provoquer de l\'angoisse']/2)),
	'Infos Don : provoquer de l\'angoisse'	=> round(2 + .12 * $niv).' cibles.pos, Eff.niv def mag -'.round(2 + $skills['Don : provoquer de l\'angoisse']).', def soc -'.round(5 + 2 * $skills['Don : provoquer de l\'angoisse']),
	'Mana Don : provoquer de l\'angoisse'	=> 3,

	'Don : sourire gagnant'					=> round($bonus_eq['p']['att']['soc'] * (2 * $att['cha'] + $att['con'] + 2 * $skills['Don : sourire gagnant'])			+ $bonus['att']['Social']),
	'Infos Don : sourire gagnant'			=> round(.1 * $niv). ' cibles.grp, 4T Adr,Vit -'.round(.25 * $skills['Don : sourire gagnant']).', ini -'.round($skills['Don : sourire gagnant']).
											', action -1, att cac -'.round(1.5 * $skills['Don : sourire gagnant']).', att cad,mag -'.round($skills['Don : sourire gagnant']).
											', def cac -'.round($skills['Don : sourire gagnant']).', def cad,mag -'.round(.5 * $skills['Don : sourire gagnant']),
	'Mana Don : sourire gagnant'			=> 6,

	'Attaque Don : maître des animaux sauvages'	=> round($bonus_eq['p']['att']['eff'] * (.75 * (2 * $att['vol'] + $att['cha'] + 2 * $skills['Don : maître des animaux sauvages']))	+ $bonus['att']['Effroi']),
	'Effet Don : maître des animaux sauvages' 	=> round($att['int']/2 + $att['cha']/3 + $skills['Don : maître des animaux sauvages']/2),
	'Infos Don : maître des animaux sauvages'	=> round(1 + .12 * $niv). ' cibles.grp, 3T Adr,Vit -'.round(.5 * $skills['Don : maître des animaux sauvages']).', action -1, def cac,cad,mag -'.
												round(1.5 * $skills['Don : maître des animaux sauvages']),
	'Mana Don : maître des animaux sauvages'	=> 7,

	'Don : maître des racines'				=> round($bonus_eq['p']['att']['fdn'] * (.7 * (2 * $att['cha'] + $att['vol'] + 2 * $skills['Don : maître des racines'])) + $bonus['att']['FdN']),
	'Infos Don : maître des racines'		=> round(.2 * $niv). ' cibles.pos, 5T Adr -'.round(.75 * $skills['Don : maître des racines']).', Vit -'.round(.5 * $skills['Don : maître des racines']).
											', ini -'.round(2 * $skills['Don : maître des racines']).', att cac,cad -'.round(2 * $skills['Don : maître des racines']).', def cac,cad -'.
											round(4 * $skills['Don : maître des racines']).', Eff.ill (T+5) Adr -'.round(.25 * $skills['Don : maître des racines']).', ini -'.
											round($skills['Don : maître des racines']).', Eff.ill PV & R.PV -'.round(.75 * $skills['Don : maître des racines']),
	'Mana Don : maître des racines'			=> 15,

    'Attaque Don : ondes et fumée' 			=> round($bonus_eq['p']['att']['exp'] * (0.55 * (2 * $att['per'] + $att['int'] + 2 * $skills['Don : ondes et fumée']))	+ $bonus['att']['Explosion']),
    'Effet Don : ondes et fumée' 			=> round($att['int']/2 + $att['vol']/3 + $skills['Don : ondes et fumée']/2),
    'Infos Don : ondes et fumée' 			=> round(1 + .1 * $niv). ' cibles.pos, 5T Per -'.round(.5 * $skills['Don : ondes et fumée']).', ini -'.round(.5 * $skills['Don : ondes et fumée']).
											', def soc -25%, def cac,cad -'.round(.5 * $skills['Don : ondes et fumée']),

    'Don : accaparer l\'attention' 			=> round($bonus_eq['p']['att']['soc'] * (2 * (2 * $att['per'] + $att['int'] + 3 * $skills['Don : accaparer l\'attention']))	+ $bonus['att']['Social']),
    'Infos Don : accaparer l\'attention' 	=> '2 cibles.pos, 2T Int -'.round(.25 * $skills['Don : accaparer l\'attention']).', ini -'.round(.75 * $skills['Don : accaparer l\'attention']).
											', att mag,soc -'.round(1.5 * $skills['Don : accaparer l\'attention']).', talents * -'.round(.75 * $skills['Don : accaparer l\'attention']),
	'Mana Don : accaparer l\'attention'		=> 3,

    'Attaque Don : malédiction du froid' 	=> round($bonus_eq['p']['att']['soc'] * (1.25 * (2 * $att['vol'] + $att['cha'] + 2 * $skills['Don : malédiction du froid'])) + $bonus['att']['Social']),
    'Effet Don : malédiction du froid' 		=> round(.05 * ($att['int']/2 + $att['cha']/3 + $skills['Don : malédiction du froid']/2)),
    'Infos Don : malédiction du froid' 		=> round(.1 * $niv). ' cibles.pos, 10T sens.deg gla +25%/+25%/+25%',
	'Mana Don : malédiction du froid'		=> 5,

	'Attaque Don : jet de glace mortel' 	=> round($bonus_eq['p']['att']['mag'] * (2 * $att['int'] + $att['vol'] + 2 * $skills['Don : jet de glace mortel']) 		+ $bonus['att']['Magie']),
    'Effet Don : jet de glace mortel' 		=> round($att['vol']/2 + $att['con']/3 + $skills['Don : jet de glace mortel']/2),
    'Infos Don : jet de glace mortel' 		=> round(.14 * $niv). ' cibles.pos, 3T att cac,cad,soc & def cac,cad -'.round(.75 * $niv + 1.5 * $skills['Don : jet de glace mortel']).', T1 R.PV -'.
											round($skills['Don : jet de glace mortel']).', ini -'.round(.75 * $niv).', T2 R.PV -'.round(2 * $skills['Don : jet de glace mortel']).
											', ini -'.round(.75 * $niv + 2 * $skills['Don : jet de glace mortel']).', T3 R.PV -'.round($skills['Don : jet de glace mortel']).
											', ini -'.round(.75 * $niv + 2 * $skills['Don : jet de glace mortel']),
    'Mana Don : jet de glace mortel' 		=> 15,

    'Attaque Don : malédiction de la chaleur'=>round($bonus_eq['p']['att']['soc'] * (1.25 * (2 * $att['vol'] + $att['cha'] + 2 * $skills['Don : malédiction de la chaleur'])) + $bonus['att']['Social']),
    'Effet Don : malédiction de la chaleur'	=> round(.05 * ($att['int']/2 + $att['cha']/3 + $skills['Don : malédiction de la chaleur']/2)),
    'Infos Don : malédiction de la chaleur' => round(.1 * $niv). ' cibles.pos, 10T sens.deg feu +25%/+25%/+25%',
	'Mana Don : malédiction de la chaleur'	=> 5,

    // SOINS =======================================================================================================

    'Prendre une potion curative' 			=>  round($att['vol']/2 + $att['cha']/3 + $skills['Prendre une potion curative']/2),
    'Prendre une potion de mana' 			=>  round($att['per']/2 + $att['cha']/3 + $skills['Prendre une potion de mana']/2),

    'Premiers secours' 						=> round($att['cha']/2 + $att['adr']/3 + $skills['Premiers secours']/2 + (.67 * $skills['Don : anatomie'])),
    'Mana Premiers secours' 				=> '=1',

    // alchimiste
    'Guérison alchimique' 					=> round(1.5 * ($att['vol']/2 + $att['cha']/3 + $skills['Guérison alchimique']/2) + (.67 * $skills['Don : anatomie'])),
    
    'Infos Soins préventifs' 				=> round(3 + .25 * $niv). ' cibles.grp, 3T R.PV +'.round($skills['Soins préventifs'] + (.67 * $skills['Don : anatomie'])),
    'Mana Soins préventifs' 				=> 7,

    'Régénération alchimique' 				=> round(2.5 * ($att['vol']/2 + $att['cha']/3 + $skills['Régénération alchimique']/2) + (.67 * $skills['Don : anatomie'])),
    'Mana Régénération alchimique' 			=> 9,
    
    'Infos Renforcement expérimental' 		=> round(1 + .13 * $niv). ' cibles.grp, 3T dom.cac'.
											' cont +'.round(.34 * $skills['Renforcement expérimental']).'/+'.round(.34 * $skills['Renforcement expérimental']).'/+'.round(.34 * $skills['Renforcement expérimental']).
											' coup +'.round(.25 * $skills['Renforcement expérimental']).'/+'.round(.25 * $skills['Renforcement expérimental']).'/+'.round(.25 * $skills['Renforcement expérimental']).
											' perf +'.round(.17 * $skills['Renforcement expérimental']).'/+'.round(.17 * $skills['Renforcement expérimental']).'/+'.round(.17 * $skills['Renforcement expérimental']).
											', For +'.round(.5 * $skills['Renforcement expérimental']).
											', R.PV +'.round(.34 * $skills['Renforcement expérimental']),
    'Mana Renforcement expérimental' 		=> 3,

    'Infos Poussée de mana expérimentale' 	=> round(1 + .34 * $niv). ' cibles.grp, 3T R.PM +'. round(.34 * $skills['Poussée de mana expérimentale']),
    'Mana Poussée de mana expérimentale'  	=> 5,

    'Guérison alchimique de masse' 			=> round(.85 * ($att['vol']/2 + $att['cha']/3 + $skills['Guérison alchimique de masse']/2) + (.67 * $skills['Don : anatomie'])),
    'Infos Guérison alchimique de masse'	=> round(.5 * $niv). ' cibles.grp',
    'Mana Guérison alchimique de masse' 	=> 9,

	'Infos Restauration alchimique de mana'	=> '1 cible, Eff.ill PM +'.$skills['Restauration alchimique de mana'],
	'Mana Restauration alchimique de mana'	=> 3,
    
    // barde
    'Chant de guérison' 					=> round(.8 * ($att['cha']/2 + $att['int']/3 + $skills['Chant de guérison']/2)),
    'Mana Chant de guérison' 				=> 2,

    'Grand chant de guérison' 				=> round(.5 * ($att['cha']/2 + $att['int']/3 + $skills['Grand chant de Guérison']/2)),
    'Infos Grand chant de guérison'			=> round(.25 * $niv). ' cibles.grp',
    'Mana Grand chant de guérison' 			=> 5,

    // chamane
    'Paroles apaisantes' 					=> round(.75 * ($att['cha']/2 + $att['int']/3 + $skills['Paroles apaisantes']/2)),
    'Mana Paroles apaisantes' 				=> 2,

    // pretre, paladin
    'Imposition des mains' 					=> round(.75 * ($att['cha']/2 + $att['vol']/3 + (1.5 * ($skills['Imposition des mains']/2  + .5 * $skills['Foi'])))),
    'Mana Imposition des mains' 			=> 4,

	'Don de Rashon' 						=> round(1.5 * (.5 * ($att['cha']/2 + $att['vol']/3 + $skills['Imposition des mains']/2))),
    'Mana Don de Rashon' 					=> 7,

    // BUFFS =======================================================================================================

	// alchimiste
    'Infos Utiliser des herbes médicinales'	=> round(1 + .13 * $niv).' cibles.pos',
    'Mana Utiliser des herbes médicinales' 	=> 6,

	'Infos Utiliser une potion alchimique'	=> round(1 + .05 * $niv).' cibles.pos',
 
	'Infos Conseils santé'					=> round(2 + .1 * $niv).' cibles.grp, Eff.ill def mal +'.round(2 + .5 * $skills['Conseils santé']),
	'Mana Conseils santé'					=> 3,
 
 	'Infos Don : providence'				=> 'Eff.ill Per +'.round(1 + .25 * $skills['Don : providence']).', ini +'.round(2 + .5 * $skills['Don : providence']).
											', R.PM -'.round(1 + .25 * $skills['Don : providence']).', def cac,mag +'.round(2 + .5 * $skills['Don : providence']).
											', def cad,piè,emb +'.round(4 + $skills['Don : providence']).', def fdn +'.round(1 + .25 * $skills['Don : providence']),
	'Mana Don : providence'					=> 4,
 
  	'Infos Don : repos total'				=> '1 cible.grp, Eff.niv act=0, R.PM +'.round(3 + $skills['Don : repos total']).', def cac,cad,mag,soc,piè,fdn,emb,mal,orien,expl +10%',
	'Mana Don : repos total'				=> 6,
 
   	'Infos Don : sommeil réparateur'		=> '1 cible.grp, Eff.niv act=0, R.PV +'.round(3 * $skills['Don : sommeil réparateur']).', def cac,cad,mag,soc,piè,fdn,emb,mal,orien,expl +10%',
	'Mana Don : sommeil réparateur'			=> 9,
 
     // archer
    'Infos Simuler'							=> '1T att cad +'.(1 + $skills['Simuler']),
    'Mana Simuler' 							=>  '=1',

	'Infos Découvrir une embuscade'			=> '7T def emb +'.$skills['Découvrir une embuscade'],
	'Mana Découvrir une embuscade'			=> '=1',

	'Infos Découvrir des pièges'			=> round(1 + .34 * $niv).' cibles.pos, 2T def pièges +'.(2 * $skills['Découvrir des pièges']),
	'Mana Découvrir des pièges'				=> '=1',

	'Infos Flèche incendiaire'				=> 'T1 dom.deg.cad feu +'.round(.5 * $skills['Flèche incendiaire']).'/+'.round(.67 * $skills['Flèche incendiaire']).'/+'.round(.75 * $skills['Flèche incendiaire']),
	'Mana Flèche incendiaire'				=> 2,

	'Infos Charge de glace'					=> 'T1 dom.deg.cad gla +'.round(.5 * $skills['Charge de glace']).'/+'.round(.67 * $skills['Charge de glace']).'/+'.round(.75 * $skills['Charge de glace']),
	'Mana Charge de glace'					=> 2,

	'Infos Flèche empoisonnée'				=> 'T1 dom.deg.cad pois +'.round(.34 * $skills['Flèche empoisonnée']).'/+'.round(.5 * $skills['Flèche empoisonnée']).'/+'.round(.67 * $skills['Flèche empoisonnée']),
	'Mana Flèche empoisonnée'				=> 2,

	'Infos Carreau empoisonné'				=> 'T1 dom.deg.cad pois +'.round(.34 * $skills['Carreau empoisonné']).'/+'.round(.5 * $skills['Carreau empoisonné']).'/+'.round(.67 * $skills['Carreau empoisonné']),
	'Mana Carreau empoisonné'				=> 2,

	'Infos Double charge'					=> 'T1 dom.deg.cad feu +'.round(.5 * $skills['Double charge']).'/+'.round(.67 * $skills['Double charge']).'/+'.round(.75 * $skills['Double charge']),
	'Mana Double charge'					=> 2,

	'Infos Viser la cible'					=> 'T1 ini -95%, def cac,cad -66%, Tir de précision à l\'arbalète/Tir de précision à l\'arc/Pierre de David +'.round(1.25 * $skills['Viser la cible']),
	'Mana Viser la cible'					=> 3,

	'Infos Don : galant'					=> '7T att&par soc +'.round(3 + $skills['Don : galant']),
	'Mana Don : galant'						=> '=1',

	'Infos Don : précision de tir inégalée'	=> '4T att cad +'.round(1 + .25 * $skills['Don : précision de tir inégalée']).', Eff.talents Tir de précision à l\'arbalète/arc/Pierre de David +'.
											round(2 + .5 * $skills['Don : précision de tir inégalée']),
	'Mana Don : précision de tir inégalée'	=> 4,

	'Infos Don : bénédiction du calme extérieur'=> 'T1 act=0, def cac,cad -25%, def mag,piè,emb -50%, 2T ini -70%, actions +'.round(1 + .1 * $skills['Don : bénédiction du calme extérieur']).
											', def soc +25%, talents & eff.talents Tir de pré à l\'arbalète/arc/Pierre de David +'.round(1.5 * $skills['Don : bénédiction du calme extérieur']),
	'Mana Don : bénédiction du calme extérieur'	=> 7,

	'Infos Don : tireur doué'				=> '4T att cad +'.round(2 + .75 * $skills['Don : tireur doué']).', eff.talents arc,arbalète,fronde +'.round(2 + .75 * $skills['Don : tireur doué']).
											', double tir,tir transp,pluie flèches,frumol +'.round(1 + .5 * $skills['Don : tireur doué']),
	'Mana Don : tireur doué'				=> 2,

	'Infos Don : maître de la poudre noire'	=> '4T att cad +'.round(1 + .25 * $skills['Don : maître de la poudre noire']).', eff.talent artificier +'.round(2 + .5 * $skills['Don : maître de la poudre noire']),
	'Mana Don : maître de la poudre noire'	=> '=1',
 
    // barbare
    'Infos Feinte' 							=> 'att cac +'.(1 + $skills['Feinte']),
    'Mana Feinte' 							=> '=1',

    'Infos Défaillance surprenante'			=> 'dom.cac perf,coup,cont +'.$skills['Défaillance surprenante'].'/+'.$skills['Défaillance surprenante'].'/+'.$skills['Défaillance surprenante'], 
    'Mana Défaillance surprenante'			=> '=1',

    'Infos Résistance du bois' 				=> '3T def.deg.cac cont,tran,perf +'. round(.5 * $skills['Résistance du bois']).'/0/0',
    'Mana Résistance du bois' 				=> '=1',

    'Infos Résistance de la pierre' 		=> '3T def.deg.cad cont,tran,perf +'. round(.5 * $skills['Résistance de la pierre']).'/0/0',
    'Mana Résistance de la pierre' 			=> '=1',

	'Infos Acrobatie'						=> '3T Éviter un coup/tir +'.round(.25 * $skills['Acrobatie']),
    'Mana Acrobatie' 						=> 3,

	'Infos Rage berserker'					=> 'T1 dom.deg.cac cont,tran,perf 0/+'.round(.5 * $skills['Rage berserker']).'/+'.round(.5 * $skills['Rage berserker']).', action +'.round(1 + .07 * $skills['Rage berserker']).
											', def cac,cad,mag,préc,imm -25%',
    'Mana Rage berserker' 					=> 7,

	'Infos Coup fracassant'					=> 'T1 dom.deg.cac cont +'.round(1 + .5 * $skills['Coup fracassant']).'/+'.round(3 + .75 * $skills['Coup fracassant']).'/+'.round(5 + $skills['Coup fracassant']).', att cac -10%',
	'Mana Coup fracassant'					=> 2,

	'Infos Résistance du fer'				=> '3T def.deg feu,pois,aci +'.round(.5 * $skills['Résistance du fer']).'/0/0',
	'Mana Résistance du fer'				=> '=1',

	'Infos Danse de guerre'					=> round(1 + .1 * $niv).' cibles.pos, 3T dom.dég.cac cont,tran,perf +1/+'.round(1 + .2 * $skills['Danse de guerre']).'/+'.round(1 + .2 * $skills['Danse de guerre']).
											', For,Adr +'.round(1 + .2 * $skills['Danse de guerre']).', Per -'.round(2 + .2 * $skills['Danse de guerre']).', ini +'.round(2 + .75 * $skills['Danse de guerre']).
											', att cac +'.round($skills['Danse de guerre']).', att cad -'.round(1.5 * $skills['Danse de guerre']).', att mag -'.round($skills['Danse de guerre']).
											', def cad -'.round(1.5 * $skills['Danse de guerre']).', def soc +'.round(1.5 * $skills['Danse de guerre']),
	'Mana Danse de guerre'					=> 3,

	'Infos Don : stabilité'					=> '5T def cac +'.round(2 + 2 * $skills['Don : stabilité']),
	'Mana Don : stabilité'					=> 2,

	'Infos Don : précision mortelle (armes de jet)'	=> '4T att cad +'.round(3 + .5 * $skills['Don : précision mortelle (armes de jet)']).', eff.talent armes de jet +'.round($skills['Don : précision mortelle (armes de jet)']),
	'Mana Don : précision mortelle (armes de jet)'	=> 4,

	'Infos Don : soupe au lait'				=> '7T att cac +'.round(2 + .75 * $skills['Don : soupe au lait']),
	'Mana Don : soupe au lait'				=> '=1',

	'Infos Don : maître de la hache'		=> '4T att cac +'.round(2 + .5 * $skills['Don : maître de la hache']).', eff.talents combat hache,dbl coup,coup circul +'.round(1 + .75 * $skills['Don : maître de la hache']),
	'Mana Don : maître de la hache'			=> 3,

 	// barde
	'Infos Chant de courage'				=> round(2 + .17 * $niv).' cibles.pos',
	'Mana Chant de courage'					=> 3,

	'Infos Grand chant de courage'			=> round(.25 * $niv).' cibles.grp',
	'Mana Grand chant de courage'			=> 5,

	'Infos Chanson de courage'				=> '1T att cac +'.$skills['Chanson de courage'],
	'Mana Chanson de courage'				=> '=1',

	'Infos Chanson de l\'entêtement'		=> '1T def soc +'.$skills['Chanson de l\'entêtement'],
	'Mana Chanson de l\'entêtement'			=> '=1',

	'Infos Chansonnette de rapidité'		=> '1T Vit +'.round(.75 * $skills['Chansonnette de rapidité']),
	'Mana Chansonnette de rapidité'			=> '=1',

	'Infos Chansonnette de la vision d\'ensemble' 	=> '1T ini +'.round(.5 * $skills['Chansonnette de la vision d\'ensemble']).', att cac +'.$skills['Chansonnette de la vision d\'ensemble'].', def cac +'.round(.75 * $skills['Chansonnette de la vision d\'ensemble']).
													', Combat à l\'épée/Combat à l\'arme de choc/Escrime +'.round(1 + .2 * $skills['Chansonnette de la vision d\'ensemble']).', eff.talents.att.cac +'.round(2 + .5 * $skills['Chansonnette de la vision d\'ensemble']),
	'Mana Chansonnette de la vision d\'ensemble'	=> 2,

	'Infos Don : duelliste'					=> '4T att cac +'.round(.5 * $skills['Don : duelliste']).', def cac +'.round(2 + .5 * $skills['Don : duelliste']).
											', eff.talents escrime/coup d\'estoc/dbl touche +'.round(2 + .75 * $skills['Don : duelliste']).'/+'.round(1 + .34 * $skills['Don : duelliste']).'/+'.
											round(1 + .34 * $skills['Don : duelliste']),
	'Mana Don : duelliste'					=> 3,

	'Infos Don : mélodie de l\'aigle'		=> '1T Per +'.round(.75 * $skills['Don : mélodie de l\'aigle']).', def piè,emb +'.round(2.5 * $skills['Don : mélodie de l\'aigle']),
	'Mana Don : mélodie de l\'aigle'		=> 2,

	'Infos Don : virtuose des sons guérisseurs'	=> '6T eff.talents compos de guérison +'.round(4 + 1.5 * $skills['Don : virtuose des sons guérisseurs']),
	'Mana Don : virtuose des sons guérisseurs'	=> 3,
 
	// chaman
	'Infos Nature du serpent (lui-même)'	=> 'Eff.niv Int,Cha +'.round(.25 * $skills['Nature du serpent (lui-même)']).', att soc +'.round(.5 * $skills['Nature du serpent (lui-même)']),
	'Mana Nature du serpent (lui-même)'		=> 2,

	'Infos Nature de l\'aigle (lui-même)'	=> 'Eff.niv Per +'.round(.34 * $skills['Nature de l\'aigle (lui-même)']).', att cad,préc,imm +'.round(.5 * $skills['Nature de l\'aigle (lui-même)']).
											', Tir à la sarbacane +'.round(1 + .2 * $skills['Nature de l\'aigle (lui-même)']).', Tir à répétition +'.round(.17 * $skills['Nature de l\'aigle (lui-même)']),
	'Mana Nature de l\'aigle (lui-même)'	=> 2,

	'Infos Nature de l\'ours (lui-même)'	=> 'Eff.niv For +'.round(.34 * $skills['Nature de l\'ours (lui-même)']).', att cac +'.round(.5 * $skills['Nature de l\'ours (lui-même)']).
											', Main griffue +'.round(.25 * $skills['Nature de l\'ours (lui-même)']).', Eff Main griffue +'.round(.25 * $skills['Nature de l\'ours (lui-même)']),
	'Mana Nature de l\'ours (lui-même)'		=> 2,

	'Infos Les esprits de la nature'		=> 'Eff.ill def fdn +'.round(2 + .75 * $skills['Les esprits de la nature']).', T+1 ini +'.round(1 + .25 * $skills['Les esprits de la nature']),
	'Mana Les esprits de la nature'			=> 4,

	'Infos Nature du serpent (compagnon)'	=> '2 cibles.pos, Eff.niv Int,Cha +'.round(.25 * $skills['Nature du serpent (compagnon)']).', att soc +'.round(.5 * $skills['Nature du serpent (compagnon)']),
	'Mana Nature du serpent (compagnon)'	=> '=1',

	'Infos Nature de l\'aigle (compagnon)'	=> '2 cibles.pos, Eff.niv Per +'.round(.34 * $skills['Nature de l\'aigle (compagnon)']).', att cad,préc,imm +'.round(.5 * $skills['Nature de l\'aigle (compagnon)']),
	'Mana Nature de l\'aigle (compagnon)'	=> '=1',

	'Infos Nature de l\'ours (compagnon)'	=> '2 cibles.pos, Eff.niv For +'.round(.34 * $skills['Nature de l\'ours (compagnon)']).', att cac +'.round(.5 * $skills['Nature de l\'ours (compagnon)']),
	'Mana Nature de l\'ours (compagnon)'	=> '=1',

	'Infos Nature du serpent (groupe)'		=> round(1 + .17 * $niv).' cibles.pos, Eff.niv Int,Cha +'.round(.25 * $skills['Nature du serpent (groupe)']).', att soc +'.round(.5 * $skills['Nature du serpent (groupe)']),
	'Mana Nature du serpent (groupe)'		=> 3,

	'Infos Nature de l\'aigle (groupe)'		=> round(1 + .17 * $niv).' cibles.pos, Eff.niv Per +'.round(.34 * $skills['Nature de l\'aigle (groupe)']).', att cad,préc,imm +'.round(.5 * $skills['Nature de l\'aigle (groupe)']),
	'Mana Nature de l\'aigle (groupe)'		=> 3,

	'Infos Nature de l\'ours (groupe)'		=> round(1 + .17 * $niv).' cibles.pos, Eff.niv For +'.round(.34 * $skills['Nature de l\'ours (groupe)']).', att cac +'.round(.5 * $skills['Nature de l\'ours (groupe)']),
	'Mana Nature de l\'ours (groupe)'		=> 3,

	'Infos Colère de l\'ours'				=> 'T1 dom.deg cont,tran,perf +2/+'.round(2 + .34 * $skills['Colère de l\'ours']).'/+'.round(2 + .34 * $skills['Colère de l\'ours']).', For +'.
											round(.34 * $skills['Colère de l\'ours']).', Vit -'.round(.2 * $skills['Colère de l\'ours']).', action +'.round(1 + .05 * $skills['Colère de l\'ours']).
											', def cac,cad,mag -20%',
	'Mana Colère de l\'ours'				=> 5,

	'Infos Vol de l\'aigle'					=> 'T1 ini +'.round(1.5 * $skills['Vol de l\'aigle']).', T2 ini +'.round($skills['Vol de l\'aigle']).', T3 ini +'.round(.5 * $skills['Vol de l\'aigle']),
	'Mana Vol de l\'aigle'					=> 2,

	'Infos Coriacité de l\'ours'			=> '1 cible, Eff.ill def.deg cont,tran,perf +'.round(.1 * $niv + .2 * $skills['Coriacité de l\'ours']).'/+'.round(.1 * $skills['Coriacité de l\'ours']).'/0, PV +'.
											round(2 + .2 * $skills['Coriacité de l\'ours']).', def mal +'.round(3 + .5 * $skills['Coriacité de l\'ours']),
	'Mana Coriacité de l\'ours'				=> 4,

	'Infos Vision de l\'aigle'				=> 'tt.pos, T1 def emb +'.round(3 + .5 * $skills['Vision de l\'aigle']),
	'Mana Vision de l\'aigle'				=> 2,

	'Infos Dépeçage du serpent'				=> '1 cible, Eff.ill PV +'.round(.75 * $skills['Dépeçage du serpent']).', 4T R.PV +'.round(2 * $skills['Dépeçage du serpent']).
											', Eff.niv action=0, def cac,cad -10%',
	'Mana Dépeçage du serpent'				=> 7,

	'Infos Don : maître de la grêle'		=> '2T att fdn +'.round(1 + $skills['Don : maître de la grêle']).', talents grêlon,grêle +'.round(.34 * $skills['Don : maître de la grêle']),
	'Mana Don : maître de la grêle'			=> 4,

	'Infos Don : effrayant'					=> '3T att eff +'.round(4 + $skills['Don : effrayant']).', talents appel & rire hyène +'.round(1 + .2 * $skills['Don : effrayant']),
	'Mana Don : effrayant'					=> '=1',

	'Infos Don : patient chasseur'			=> '3T Per +'.round(.34 * $skills['Don : patient chasseur']).', ini +'.round(.5 * $skills['Don : patient chasseur']).', att pré +'.
											round(3 + .5 * $skills['Don : patient chasseur']).', talents tir sarbacane,répétition +'.round(.34 * $skills['Don : patient chasseur']),
	'Mana Don : patient chasseur'			=> 3,

	'Infos Don : griffes du tigre'			=> '3T talent mains griffue +'.round(.5 * $skills['Don : griffes du tigre']).', eff.talents mains griffues +'.round($skills['Don : patient chasseur']),
	'Mana Don : griffes du tigre'			=> 2,

	// chasseur
	'Mana Empoisonner son arme'				=> 3,

	'Infos Poison pour armes'				=> round(1 + .2 * $niv).' cibles.pos',
	'Mana Poison pour armes'				=> 4,

	'Infos Don : bénédiction de la flèche'	=> '4T att cad +'.round(2 + .5 * $skills['Don : bénédiction de la flèche']).', eff.talent tir arc,dbl tir,pluie flèches, tir pré arc +'.
											round(.5 * $skills['Don : bénédiction de la flèche']),
	'Mana Don : bénédiction de la flèche'	=> 4,

	'Infos Don : bénédiction de Chasun'		=> round(.2 * $niv).' cibles.grp, 5T Vit,Per +1, def fdn +'.round(1 + .75 * $skills['Don : bénédiction de Chasun']),
	'Mana Don : bénédiction de Chasun'		=> 6,

	'Infos Don : cachotterie'				=> '3T Adr +2, ini +'.round($skills['Don : cachotterie']).', att emb +'.round(3 + .75 * $skills['Don : cachotterie']),
	'Mana Don : cachotterie'				=> 3,

	'Infos Don : maître de la chasse'		=> '4T att cac +'.round(2 + .75 * $skills['Don : maître de la chasse']).', eff.talents combat hast,couteau +'.round(1 + .66 * $skills['Don : maître de la chasse']),
	'Mana Don : maître de la chasse'		=> 3,

	// chevalier
    'Infos Capitaine' 						=> round(2 + .13 * $niv).' cibles.pos, 3T '.
											'deg.cac +'. round(.34 * $skills['Capitaine']).  '/+'. round(.5 * $skills['Capitaine']).  '/+'. round(.67 * $skills['Capitaine']).
											', deg.cad +'. round(.25 * $skills['Capitaine']).  '/+'. round(.34 * $skills['Capitaine']).  '/+'. round(.5 * $skills['Capitaine']).
											', init +'. round(.5 * $skills['Capitaine']).
											', att cac,cad +'. round(.3 * $skills['Capitaine']). '/+'. round(.2 * $skills['Capitaine']). ','.
											' def cac,cad +'. round(.2 * $skills['Capitaine']).'/+'. round(.1 * $skills['Capitaine']),
    'Mana Capitaine' 						=> 4,
    
    'Infos Maréchal de camp' 				=> round(2 + .2 * $niv).' cibles.grp, 3T, '.
											'deg cac +'. round(.34 * $skills['Maréchal de camp']).  '/+'. round(.5 * $skills['Maréchal de camp']).  '/+'. round(.67 * $skills['Maréchal de camp']).
											', deg cad +'. round(.25 * $skills['Maréchal de camp']).  '/+'. round(.34 * $skills['Maréchal de camp']).  '/+'. round(.5 * $skills['Maréchal de camp']).
											', init +'. round(.5 * $skills['Maréchal de camp']).
											', att cac,cad +'. round(.3 * $skills['Maréchal de camp']). '/+'. round(.2 * $skills['Maréchal de camp']).
											', def cac,cad +'. round(.2 * $skills['Maréchal de camp']). '/+'. round(.1 * $skills['Maréchal de camp']),
    'Mana Maréchal de camp' 				=> 6,

	'Infos Fidélité vassale'				=> round(.25 * $niv).' cibles.grp, 2T def.deg psy +'.round(.5 * $skills['Fidélité vassale']).'/+'.round(.25 * $skills['Fidélité vassale']).'/+'.
											round(.25 * $skills['Fidélité vassale']).', def soc +'.round(.5 * $skills['Fidélité vassale']),
	'Mana Fidélité vassale'					=> 4,

	'Infos Cœur de lion'					=> '3T dom.deg cont,tran,perf +'.round(.25 * $skills['Cœur de lion']).'/+'.round(.25 * $skills['Cœur de lion']).'/+'.round(.25 * $skills['Cœur de lion']).
											', ini +'.round(.5 * $skills['Cœur de lion']).', action +1, att cac +'.round(.5 * $skills['Cœur de lion']).', 10T (T+1) R.PV -'.round(.5 * $niv),
	'Mana Cœur de lion'						=> 15,

	'Infos Don : avoir un caractère fort'	=> '3T att cac +'.round(2 + .75 * $skills['Don : avoir un caractère fort']).', def mal +'.round(1.25 * $skills['Don : avoir un caractère fort']),
	'Mana Don : avoir un caractère fort'	=> 4,

	'Infos Don : vétéran du champ de bataille'	=> '4T att cac +'.round(1 + .5 * $skills['Don : vétéran du champ de bataille']).', def cac +'.round(1 + .25 * $skills['Don : vétéran du champ de bataille']).
												', eff.talent combat hast,épée,choc +'.round(1 + .75 * $skills['Don : vétéran du champ de bataille']),
	'Mana Don : vétéran du champ de bataille'	=> 3,

	'Infos Don : maître de l\'acier forgé'	=> '4T def cac,cad +'.round(.75 * $skills['Don : maître de l\'acier forgé']).', talent par bouclier +'.round(1 + .25 * $skills['Don : maître de l\'acier forgé']),
	'Mana Don : maître de l\'acier forgé'	=> 3,

	// érudit
    'Infos Sages conseils' 					=> round(1 + .2 * $niv).' cibles.pos',
    'Mana Sages conseils' 					=> 4,

    'Infos Exposé scientifique' 			=> round(1 + .2 * $niv).' cibles.grp',
    'Mana Exposé scientifique' 				=> 5,    

	'Infos Balle acide'						=> 'T1 dég.cad aci +'.round(.5 * $skills['Balle acide']).'/+'.round(.67 * $skills['Balle acide']).'/+'.round(.75 * $skills['Balle acide']),
	'Mana Balle acide'						=> 2,

	'Infos Transfert de mana'				=> round(4 + .34 * $niv).' cibles.pos, T1 R.PM +'.$skills['Transfert de mana'],
	'Mana Transfert de mana'				=> 7,

	'Infos Don : maître de la fronde'		=> '4T Per +'.round(1 + .25 * $skills['Don : maître de la fronde']).', att cad +'.round(1 + .5 * $skills['Don : maître de la fronde']).
											', eff.talent tir fronde +'.round(2 + $skills['Don : maître de la fronde']),
	'Mana Don : maître de la fronde'		=> 2,

	// gladiateur
	'Infos Maître d\'armes'					=> '2T att cac +'.round(.34 * $skills['Maître d\'armes']),
	'Mana Maître d\'armes'					=> 3,

    'Infos Précision mortelle (armes tranchantes)'	=> 'perm dom.cac tran +'. round(.25 * $skills['Précision mortelle (armes tranchantes)']).
                                                      '/+'. round(.5 * $skills['Précision mortelle (armes tranchantes)']).
                                                      '/+'. $skills['Précision mortelle (armes tranchantes)'].
                                                      ', 3T dom cac tran +0/+'. round(.25 * $skills['Précision mortelle (armes tranchantes)']).
                                                      '/+'. round(.5 * $skills['Précision mortelle (armes tranchantes)']),
    'Mana Précision mortelle (armes tranchantes)' 	=> 5,

    'Infos Précision mortelle (armes perforantes)' 	=> 'perm dom.cac perf +'. round(.25 * $skills['Précision mortelle (armes perforantes)']).
                                                      '/+'. round(.5 * $skills['Précision mortelle (armes perforantes)']).
                                                      '/+'. $skills['Précision mortelle (armes perforantes)'].
                                                      ', 3T dom.cac perf +0/+'. round(.25 * $skills['Précision mortelle (armes perforantes)']).
                                                      '/+'. round(.5 * $skills['Précision mortelle (armes perforantes)']),
    'Mana Précision mortelle (armes perforantes)' 	=> 5,

    'Infos Précision mortelle (armes contondantes)' => 'perm dom.cac cont +'. round(.25 * $skills['Précision mortelle (armes contondantes)']).
                                                      '/+'. round(.5 * $skills['Précision mortelle (armes contondantes)']).
                                                      '/+'. $skills['Précision mortelle (armes contondantes)'].
                                                      ', 3T dom.cac cont +0/+'. round(.25 * $skills['Précision mortelle (armes contondantes)']).
                                                      '/+'. round(.5 * $skills['Précision mortelle (armes contondantes)']),
    'Mana Précision mortelle (armes contondantes)' 	=> 5,

	'Infos Changement de position (devant)'	=> 'T1 att cac '.($skills['Changement de position (devant)'] > 9?'+':'-').(2 * ($skills['Changement de position (devant)'] - 10)).' -20%, T+1 att cac '.
											($skills['Changement de position (devant)'] > 9?'+':'-').($skills['Changement de position (devant)'] - 10).' -20%, Eff.niv T+2 att cac -20%',
	'Mana Changement de position (devant)'	=> 3,

	'Infos Changement de position (derrière)'=> 'T1 att cac '.($skills['Changement de position (derrière)'] > 9?'+':'-').(2 * ($skills['Changement de position (derrière)'] - 10)).' -20%, T+1 att cac '.
											($skills['Changement de position (derrière)'] > 9?'+':'-').($skills['Changement de position (derrière)'] - 10).' -20%, Eff.niv T+2 att cac -20%',
	'Mana Changement de position (derrière)'=> 3,

	'Infos Changement de position (au centre)'=> 'T1 att cac '.($skills['Changement de position (au centre)'] > 9?'+':'-').(2 * ($skills['Changement de position (au centre)'] - 10)).' -20%, T+1 att cac '.
											($skills['Changement de position (au centre)'] > 9?'+':'-').($skills['Changement de position (au centre)'] - 10).' -20%, Eff.niv T+2 att cac -20%',
	'Mana Changement de position (au centre)'=> 3,

	'Infos Changement de position (à gauche)'=> 'T1 att cac '.($skills['Changement de position (à gauche)'] > 9?'+':'-').(2 * ($skills['Changement de position (à gauche)'] - 10)).' -20%, T+1 att cac '.
											($skills['Changement de position (à gauche)'] > 9?'+':'-').($skills['Changement de position (à gauche)'] - 10).' -20%, Eff.niv T+2 att cac -20%',
	'Mana Changement de position (à gauche)'=> 3,

	'Infos Changement de position (à droite)'=> 'T1 att cac '.($skills['Changement de position (à droite)'] > 9?'+':'-').(2 * ($skills['Changement de position (à droite)'] - 10)).' -20%, T+1 att cac '.
											($skills['Changement de position (à droite)'] > 9?'+':'-').($skills['Changement de position (à droite)'] - 10).' -20%, Eff.niv T+2 att cac -20%',
	'Mana Changement de position (à droite)'=> 3,

	'Infos Maître de l\'art du combat'		=> '1T Adr +'.round(1 + .1 * $skills['Maître de l\'art du combat']).', att cac +'.round(.5 * $skills['Maître de l\'art du combat']).
											', Combat hast/épée/choc/hache/bâton/couteau & Parade au bouclier +'.round(.2 * $skills['Maître de l\'art du combat']).
											', Effet Combat hast/épée/choc/hache/bâton/couteau +'.round(.34 * $skills['Maître de l\'art du combat']),
	'Mana Maître de l\'art du combat'		=> 2,

	'Infos Attaque en sautant'				=> '1T ini +'.round(.25 * $skills['Attaque en sautant']).', Effet Combat au couteau +'.round(1.5 * $skills['Attaque en sautant']),
	'Mana Attaque en sautant'				=> '=1',

	'Infos Pugilat'							=> 'Eff.niv dom.deg.cac cont,tranch,perf 0/+1/+'.round(1 + .34 * $skills['Pugilat']).', Cha -'.round(2 + .2 * $skills['Pugilat']).
											', Vit +'.round(.17 * $skills['Pugilat']).', ini +'.round(1 + .75 * $skills['Pugilat']).', att cac +'.round(1.5 * $skills['Pugilat']),
	'Mana Pugilat'							=> 4,

	'Infos Don : maître de la lutte'		=> '4T att cac +'.round(1 + .5 * $skills['Don : maître de la lutte']).', eff.talent combat mains nues/poings volants +'.
											round(1 + $skills['Don : maître de la lutte']).'/+'.round(1 + .67 * $skills['Don : maître de la lutte']),
	'Mana Don : maître de la lutte'			=> 3,

	'Infos Don : maître du filet noué'		=> '3T talent lancer filet +'.round(.5 * $skills['Don : maître du filet noué']),

	'Infos Don : combattant dans l\'arène'	=> '4T def mag,soc +'.round($skills['Don : combattant dans l\'arène']),
	'Mana Don : combattant dans l\'arène'	=> 2,

	'Infos Don : roi de la fanfaronnade'	=> '4T att cac,soc +'.round($skills['Don : roi de la fanfaronnade']),
	'Mana Don : roi de la fanfaronnade'		=> 2,

	// jongleur
	'Infos Divination'						=> round(.34 * $niv).' cibles.grp, 3T def cac,cad +'.round(.25 * $niv + .75 * $skills['Divination']),
	'Mana Divination'						=> 3,
 
 	'Infos Jonglerie'						=> 'tt, 2T def soc +'.round(.25 * $niv + .75 * $skills['Jonglerie']),
	'Mana Jonglerie'						=> 5,
 
 	'Infos Galipette'						=> 'T1 att cac +'.round($skills['Galipette']).', def cac,cad +'.round(.34 * $skills['Galipette']),
	'Mana Galipette'						=> 4,
 
	'Infos Lancer artistique'				=> '2T Armes de jet +'.round(.5 * $skills['Lancer artistique']).', Effet Armes de jet +'.round(.34 * $skills['Lancer artistique']),
	'Mana Lancer artistique'				=> 2,
 
	'Infos La deuxième face'				=> 'T1 def piè,emb +'.round(2 + .75 * $skills['La deuxième face']).', def fdn +'.round(.5 * $skills['La deuxième face']),
	'Mana La deuxième face'					=> 2,
 
	'Infos Don : base de la pyramide'		=> '4T For +'.round(1 + .25 * $skills['Don : base de la pyramide']).', eff.talent combat mains nues +'.round(.75 * $skills['Don : base de la pyramide']),
	'Mana Don : base de la pyramide'		=> 4,
 
	'Infos Don : cible vivante'				=> '4T att cad +'.round(1 + .5 * $skills['Don : cible vivante']).', def cad +'.round(1 + $skills['Don : cible vivante']).', eff.talent armes de jet +'.round($skills['Don : cible vivante']),
	'Mana Don : cible vivante'				=> 4,
 
	'Infos Don : maître des lames'			=> '4T dom.deg.cac tran,per 0/+'.round(2 + .25 * $skills['Don : maître des lames']).'/+'.round(2 + .5 * $skills['Don : maître des lames']).
											', att cac +'.round(2 + .5 * $skills['Don : maître des lames']),
	'Mana Don : maître des lames'			=> 3,

    // paladin
    'Infos Aura du paladin' 				=> 'dom.def cont,tran,perf,feu,gla,elec,psy,pois,aci +'. round(.2 * $skills['Aura du paladin']).  '/+'. round(.2 * $skills['Aura du paladin']).  '/0'.
											', dom.deg.cac saint +0/+'. round(.1 * $skills['Aura du paladin']).  '/+'. round(.2 * $skills['Aura du paladin']).
											', R.PV +'. round(.25 * $skills['Aura du paladin']).
											', R.PM +'. round(.2 * $skills['Aura du paladin']),

    'Infos Sphère de pouvoir' 				=> round(.25 * $niv).' cibles.pos, 1T dom.deg.cac&cad perf,coup,cont +'. round(.5 * $skills['Sphère de pouvoir']).'/+'. round(.5 * $skills['Sphère de pouvoir']).'/+'. round(.75 * $skills['Sphère de pouvoir']),
    'Mana Sphère de pouvoir' 				=> 5,

    'Infos Grande sphère de pouvoir' 		=> round(.25 * $niv).' cibles.grp, 2T dom.deg.cac&cad perf,coup,cont +'. round(.5 * $skills['Grande sphère de pouvoir']).'/+'. round(.5 * $skills['Grande sphère de pouvoir']).'/+'. round(.75 * $skills['Grande sphère de pouvoir']),
    'Mana Grande sphère de pouvoir' 		=> 8,

    'Infos Sphère de repos' 				=> round(2 + .17 * $niv).' cibles.pos, 1T R.PM +'. round(1 + .5 * $skills['Sphère de repos']),
    'Mana Sphère de repos' 					=> 5,

    'Infos Grande sphère de repos' 			=> round(3 + .17 * $niv).' cibles.grp, 2T R.PM +'. round(1 + .5 * $skills['Grande sphère de repos']),
    'Mana Grande sphère de repos' 			=> 8,

    'Infos Sphère des éléments' 			=> round(2 + .25 * $niv).' cibles.pos, 1T def.dom feu,gla,ecl,poi,aci +'. round(.5 * $skills['Sphère des éléments']).'/+'. round(.5 * $skills['Sphère des éléments']).'/0',
    'Mana Sphère des éléments' 				=> 5,

    'Infos Grande sphère des éléments' 		=> round(3 + .25 * $niv).' cibles.grp, 2T def.dom feu,gla,ecl,poi,aci +'. round(.5 * $skills['Grande sphère des éléments']).'/+'. round(.5 * $skills['Grande sphère des éléments']).'/0',
    'Mana Grande sphère des éléments' 		=> 8,

    'Infos Sphère de guérison' 				=> round(4 + .25 * $niv).' cibles.pos, 1T R.PV +'. round(1 + .5 * $skills['Sphère de guérison']),
    'Mana Sphère de guérison' 				=> 5,

    'Infos Grande sphère de guérison' 		=> round(6 + .25 * $niv).' cibles.grp, 2T R.PV +'. round(1 + .5 * $skills['Grande sphère de guérison']),
    'Mana Grande sphère de guérison' 		=> 8,

    'Infos Sphère de protection' 			=> round(1 + .34 * $niv).' cibles.pos, 1T def.dom cont,tran,perf +'. round(.5 * $skills['Sphère de protection']).'/0/0, def nat +'. $skills['Sphère de protection'],
    'Mana Sphère de protection' 			=> 5,

    'Infos Grande sphère de protection' 	=> round(2 + .34 * $niv).' cibles.grp, 2T def.dom cont,tran,perf +'. round(.5 * $skills['Grande sphère de protection']).'/0/0, def nat+'. $skills['Grande sphère de protection'],
    'Mana Grande sphère de protection' 		=> 8,

    'Infos Sphère de confiance' 			=> round(1 + .34 * $niv).' cibles.pos, 1T def.dom psy +'. round(.5 * $skills['Sphère de confiance']).'/0/0, def soc +'. $skills['Sphère de confiance'],
    'Mana Sphère de confiance' 				=> 5,

    'Infos Grande sphère de confiance' 		=> round(3 + .34 * $niv).' cibles.grp, 2T def.dom psy +'. round(.5 * $skills['Grande sphère de confiance']).'/0/0, def soc +'. $skills['Grande sphère de confiance'],
    'Mana Grande sphère de confiance' 		=> 8,

	'Infos Prière courte et fervente'		=> '2T For +'.round(2 + .34 * $skills['Prière courte et fervente']).', ini -25%, R.PV +'.round($skills['Prière courte et fervente']).', R.PM -'.
											round(1 + .5 * $skills['Prière courte et fervente']).', att cac,cad,soc -'.round(.5 * $skills['Prière courte et fervente']).', def cac,cad +'.
											round(2 + $skills['Prière courte et fervente']).', def mag,fdn +'.round(1 + .5 * $skills['Prière courte et fervente']),
	'Mana Prière courte et fervente'		=> 4,

	'Infos Don : droit champion'			=> '4T att cac +'.round(1 + .5 * $skills['Don : droit champion']).', def cac +'.round(1 + .25 * $skills['Don : droit champion']).
											', eff.talents combat épée,choc +'.round(1 + .75 * $skills['Don : droit champion']),
	'Mana Don : droit champion'				=> 3,

	'Infos Don : bénédiction de Demosan'	=> round(1 + .17 * $niv).' cibles.pos, Eff.niv Adr +1, att & def cac +'.round(2 + $skills['Don : bénédiction de Demosan']).', def.dom feu +'.round(2 + .5 * $skills['Don : bénédiction de Demosan']).
											'/+'.round(2 + .5 * $skills['Don : bénédiction de Demosan']).'/+'.round(1 + .5 * $skills['Don : bénédiction de Demosan']),
	'Mana Don : bénédiction de Demosan'		=> 4,

	'Infos Don : intuition divine'			=> '4T def tt +'.round($skills['Don : intuition divine']),
	'Mana Don : intuition divine'			=> 7,

    // pretre
    'Infos Bénir' 							=> round(1 + .25 * $skills['Bénir']).' cibles.pos, 3T def.dom feu,gla,elec +'. round(.5 * $skills['Bénir']).'/+'. round(.5 * $skills['Bénir']).'/0, def mag +'. round(.5 * $skills['Bénir']),
    'Mana Bénir' 							=> 2,

	'Infos Bouclier de Demosan'				=> '7T def.dom.cac cont,tranch,perf +'.round(.34 * $skills['Bouclier de Demosan']).'/+'.round(.34 * $skills['Bouclier de Demosan']).'/+'.round(.34 * $skills['Bouclier de Demosan']).
											', Parade au bouclier +'.round(.2 * $skills['Bouclier de Demosan']).', 6T (T+1) def.deg feu +'.round(.34 * $skills['Bouclier de Demosan']).'/+'
											.round(.34 * $skills['Bouclier de Demosan']).'/+'.round(.34 * $skills['Bouclier de Demosan']),
	'Mana Bouclier de Demosan'				=> 2,

	'Infos Bénédiction d\'Akbeth'			=> round(1 + .25 * $niv).' cibles.grp, 3T def mag +'.round(1.5 * $skills['Bénédiction d\'Akbeth']),
	'Mana Bénédiction d\'Akbeth'			=> 4,

	'Infos Bénédiction de Demosan'			=> round(1 + .25 * $niv).' cibles.pos, 2T dom.deg.cac feu +'.round(.5 * $skills['Bénédiction de Demosan']).'/+'.round(.75 * $skills['Bénédiction de Demosan']).
											'/+'.round($skills['Bénédiction de Demosan']).', dom.deg.cac saint 0/+'.round(.5 * $skills['Bénédiction de Demosan']).
											'/+'.round(.5 * $skills['Bénédiction de Demosan']).', def cac +'.round(.34 * $skills['Bénédiction de Demosan']),
	'Mana Bénédiction de Demosan'			=> 4,

	'Infos Inspiration d\'Akbeth'			=> round(1 + .2 * $niv).' cibles.grp, Eff.niv PM +'.round($skills['Inspiration d\'Akbeth']).', R.PM +'.round(.67 * $skills['Inspiration d\'Akbeth']).
											', talents classe sphère & grande sphère +'.round(.1 * $skills['Inspiration d\'Akbeth']),
	'Mana Inspiration d\'Akbeth'			=> 6,

	'Infos Guérison divine'					=> round(.25 * $niv).' cibles.pos, T+1 R.PV +'.round(5 * $skills['Guérison divine']),
	'Mana Guérison divine'					=> 5,

	'Infos Bénédiction de Rashon'			=> round(1 + .2 * $niv).' cibles.pos, 5T def mal +'.round(1.25 * $skills['Bénédiction de Rashon']).', sens dég pois -'.
											round(.5 * $skills['Bénédiction de Rashon']).'/-'.round(.34 * $skills['Bénédiction de Rashon']).'/-'.round(.25 * $skills['Bénédiction de Rashon']).
											', premier secours & impositions des mains +'.round(.2 * $skills['Bénédiction de Rashon']),
	'Mana Bénédiction de Rashon'			=> 5,

	'Infos Don : Canal divin'				=> '3T actions -'.round(.1 * $skills['Don : Canal divin']).', R.PV +'.round(.5 * $skills['Don : Canal divin']).', R.PM +'.
											round(.34 * $skills['Don : Canal divin']).', att cac,mag -'.round(2 * $skills['Don : Canal divin']).
											', def cac +'.round(1.5 * $skills['Don : Canal divin']).', def cad +'.round(1.25 * $skills['Don : Canal divin']).
											', def mag +'.round($skills['Don : Canal divin']),
	'Mana Don : Canal divin'				=> 4,

	'Infos Don : Alliance maudite'			=> 'Eff.ill dom.deg.cac cont +'.round(.5 * $skills['Don : Alliance maudite']).'/+'.round(.75 * $skills['Don : Alliance maudite']).'/+'.
											round($skills['Don : Alliance maudite']).', dom.deg feu & dom.deg.mal pois +'.round(.34 * $skills['Don : Alliance maudite']).
											'/+'.round(.5 * $skills['Don : Alliance maudite']).'/+'.round(.75 * $skills['Don : Alliance maudite']).
											', dom.deg saints -'.round(4 * $skills['Don : Alliance maudite']).'/-'.round(4 * $skills['Don : Alliance maudite']).'/-'.round(4 * $skills['Don : Alliance maudite']).
											', R.PV -'.round(.2 * $skills['Don : Alliance maudite']).', R.PM -'.round(.25 * $skills['Don : Alliance maudite']).', att cac,sort +'.
											round(2 + .75 * $skills['Don : Alliance maudite']).', att mal +'.round(1 + .34 * $skills['Don : Alliance maudite']).', sens.deg feu,gla,elec -'.
											round(2 * $skills['Don : Alliance maudite']).'/-'.round(2 * $skills['Don : Alliance maudite']).'/-'.round(2 * $skills['Don : Alliance maudite']).
											', sens.deg saints -'.round(3 * $skills['Don : Alliance maudite']).'/-'.round(3 * $skills['Don : Alliance maudite']).'/-'.
											round(3 * $skills['Don : Alliance maudite']),
	'Mana Don : Alliance maudite'			=> 6,

	'Infos Don : Langage de miel'			=> round(1 + .2 * $niv).' cibles.grp, Eff.niv def soc +'.round(1.5 * $skills['Don : Langage de miel']),

	'Infos Don : Savoir ancestral'			=> round(1 + .2 * $niv).' cibles.pos, Eff.ill talents savoir des anciens off & def +'.round(2 + .5 * $skills['Don : Savoir ancestral']),
	'Mana Don : Savoir ancestral'			=> 6,

    // risquetout
    'Infos Chef charismatique' 				=> round(1 + .2 * $niv).' cibles.pos, 2T def.dom psy +'.round(1 + .1 * $skills['Chef charismatique']).'/+'.round(1 + .1 * $skills['Chef charismatique']).'/+'.round(1 + .1 * $skills['Chef charismatique']).
											', For +'.round(1 + .13 * $skills['Chef charismatique']).', Vol +'.round(1 + .13 * $skills['Chef charismatique']).
											', ini +'.round(.5 * $skills['Chef charismatique']).', att cac +'.round(3 + .2 * $skills['Chef charismatique']).
											', def cac,cad +'.round(.2 * $skills['Chef charismatique']).', def soc +'.round(.5 * $skills['Chef charismatique']),
    'Mana Chef charismatique' 				=> 4,

	'Infos Assombrir (groupe)'				=> round(.25 * $niv).' cibles.grp, 3T def cac +'.round(.25 * $skills['Assombrir (groupe)']).', def cad +'.round(.5 * $skills['Assombrir (groupe)']).', def sort +'.round(.25 * $skills['Assombrir (groupe)']).
											', T+1 def cac +'.round(.25 * $skills['Assombrir (groupe)']).', def cad +'.round(.5 * $skills['Assombrir (groupe)']).', def sort +'.round(.25 * $skills['Assombrir (groupe)']),

	'Infos Assombrir (soi-même)'			=> '3T def cac +'.round(.25 * $skills['Assombrir (soi-même)']).', def cad +'.round(.5 * $skills['Assombrir (soi-même)']).', def sort +'.round(.25 * $skills['Assombrir (soi-même)']).
											', T+1 def cac +'.round(.25 * $skills['Assombrir (soi-même)']).', def cad +'.round(.5 * $skills['Assombrir (soi-même)']).', def sort +'.round(.25 * $skills['Assombrir (soi-même)']),

	'Infos Roi de l\'escrime'				=> '1T Adr,Vit +'.round(.2 * $skills['Roi de l\'escrime']).', Escrime +'.round(1 + .25 * $skills['Roi de l\'escrime']),
	'Mana Roi de l\'escrime'				=> 2,

	'Infos Déceler le point faible'			=> '1T Vit,Per +'.round(.2 * $skills['Déceler le point faible']).', Coup d\'estoc +'.round(1 + .25 * $skills['Déceler le point faible']),
	'Mana Déceler le point faible'			=> 2,

	'Infos Battuta'							=> '2T att cac +'.round(3 + .25 * $skills['Battuta'] + .75 * $skills['Battuta']).', T3-7 att cac +'.round(2 + .75 * $skills['Battuta']).', 7T ini +'.round(.25 * $skills['Battuta']).', def cac -'.round(1 + .34 * $skills['Battuta']),
	'Mana Battuta'							=> 2,

	'Infos Appel'							=> '7T ini +'.round(.25 * $skills['Appel']).', att cac -'.round(1 + .34 * $skills['Appel']).', def cac +'.round(2 + .75 * $skills['Appel']).', def cad +'.round(1 + .25 * $skills['Appel']),
	'Mana Appel'							=> 2,

	'Infos Botte de la flèche'				=> '1T For,Vit +'.round(.2 * $skills['Botte de la flèche']).', Double touche +'.round(1 + .25 * $skills['Botte de la flèche']),

	'Infos Botte secrète'					=> '3T Vit +'.round(.5 * $skills['Botte secrète']).', ini +'.$skills['Botte secrète'].', action +'.round(.08 * $skills['Botte secrète']).', R.PM -'.round(.75 * $skills['Botte secrète']).
											', att cac -'.round(2 + .34 * $skills['Botte secrète']).', att cad -'.round(4 + .75 * $skills['Botte secrète']),

	'Infos Don : maître à la main calme'	=> '4T att cad +'.round(1 + .25 * $skills['Don : maître à la main calme']).', eff.talent tir pistolet +'.round(2 + .5 * $skills['Don : maître à la main calme']),
	'Mana Don : maître à la main calme'		=> '=1',

	'Infos Don : tireur de duel doué'		=> '4T att cad +'.round(2 + .75 * $skills['Don : tireur de duel doué']).', eff.talent pluie de balles/tir préc pistolet +'.round(.5 * $skills['Don : tireur de duel doué']),
	'Mana Don : tireur de duel doué'		=> '=1',

    // sorcier
    'Infos Bouclier magique' 				=> '7T def.dom cont,tran,perf +'. round(.5 * $skills['Bouclier magique']).'/+'. round(.5 * $skills['Bouclier magique']).'/+'. round(.5 * $skills['Bouclier magique']),
    'Mana Bouclier magique' 				=> 4,

    'Infos Protection magique' 				=> round(1 + .25 * $niv).' cibles.pos, 3T def mag +'. round(1.5 * $skills['Protection magique']),
    'Mana Protection magique' 				=> 4,

    'Infos Régénération de mana' 			=> 'R.PM +'. round(.5 * $skills['Régénération de mana']),

    'Infos Sortilège de défense' 			=> round(1 + .25 * $niv). ' cibles.grp',
    'Mana Sortilège de défense' 			=> 9,

    'Infos Sortilège de soutien' 			=> round(1 + .25 * $niv). ' cibles.grp',
    'Mana Sortilège de soutien'  			=> 7,
    
    'Infos Méditation' 						=> 'T1 act=0, def cac -75%, cad -50%, mag -25%, 3T R.PM +'. round(2.5 * $skills['Méditation']),

    'Infos Maître du feu' 					=> '3T sorts de feu +'. round(.34 * $skills['Maître du feu']),
    'Mana Maître du feu' 					=> 3,

    'Infos Maître de la glace' 				=> '3T sorts de gla +'. round(.34 * $skills['Maître de la glace']),
    'Mana Maître de la glace' 				=> 3,

    'Infos Faveur des étoiles' 				=> '7T def.dom.mag elec,feu,gla +'.round(.34 * $skills['Faveur des étoiles']).'/+'.round(.34 * $skills['Faveur des étoiles']).'/+'.round(.34 * $skills['Faveur des étoiles']).', def mag +'. round(.5 * $skills['Faveur des étoiles']),
    'Mana Faveur des étoiles' 				=> 3,

	'Infos Ascèse'							=> 'T1 act=0, def cac,cad -66%, 5T R.PV -'.round(1 + .34 * $skills['Ascèse']).', R.PM +'.round(1.75 * $skills['Ascèse']),

	'Infos Déphasage dimensionnel'			=> 'T1 act=0, 3T R.PM -'.round(1 + $skills['Déphasage dimensionnel']).', att cac -'.round(.5 * $skills['Déphasage dimensionnel']).', att soc -'.
											round(.3 * $skills['Déphasage dimensionnel']).', def cac,cad,piège +'.round(2 * $skills['Déphasage dimensionnel']).', def mag,fdn,mal +'.
											round($skills['Déphasage dimensionnel']).', def soc +'.round(1.5 * $skills['Déphasage dimensionnel']),
	'Mana Déphasage dimensionnel'			=> 6,

	'Infos Don : boule de froid'			=> 'T1 act=0, def mag +'.round(.5 * $skills['Don : boule de froid']).', 3T R.PM +'.round(10 * $skills['Don : boule de froid']),

	'Infos Don : boule de chaleur'			=> 'T1 act=0, def mag +'.round(.5 * $skills['Don : boule de chaleur']).', 3T R.PM +'.round(10 * $skills['Don : boule de chaleur']),

	'Infos Don : bouclier de flammes'		=> round(1 + .17 * $niv). ' cibles.pos, Eff.niv ini +'.round(.5 * $skills['Don : bouclier de flammes']).', def.dom feu +'.
											round(4 + .34 * $skills['Don : bouclier de flammes']).'/+'.round(4 + .34 * $skills['Don : bouclier de flammes']).'/+'.
											round(2 + .34 * $skills['Don : bouclier de flammes']).', dom.deg.cac feu +1/+2/+2, dom.deg.cac feu +'.
											round(.25 * $skills['Don : bouclier de flammes']).'/+'.round(.5 * $skills['Don : bouclier de flammes']).'/+'.
											round(.5 * $skills['Don : bouclier de flammes']).' (z)',
	'Mana Don : bouclier de flammes'		=> 5,

	// attributs
    'Infos Sagesse' 						=> 'T1 Int +'. round(.5 * $skills['Sagesse']),
    'Mana Sagesse' 							=> 5,

    'Infos Forte volonté' 					=> 'T1 Vol +'. round(.5 * $skills['Forte volonté']),
    'Mana Forte volonté' 					=> 5,

    'Infos Œil de faucon' 					=> 'T1 Per +'. round(.5 * $skills['Œil de faucon']),
    'Mana Œil de faucon' 					=> 5,

	'Infos Détermination' 					=> 'T1 Vol +'. round(.5 * $skills['Détermination']),
    'Mana Détermination' 					=> 5,

    'Infos Charmeur' 						=> 'T1 Cha +'. round(.5 * $skills['Charmeur']),
    'Mana Charmeur' 						=> 5,

    'Infos Dextérité' 						=> 'T1 Adr +'. round(.5 * $skills['Dextérité']),
    'Mana Dextérité' 						=> 5,

    'Infos Regain de vitesse' 				=> 'T1 Vit +'. round(.5 * $skills['Regain de vitesse']),
    'Mana Regain de vitesse' 				=> 5,

    'Infos Athlétisme' 						=> 'T1 For +'. round(.5 * $skills['Athlétisme']),
    'Mana Athlétisme' 						=> 5,

    'Infos Autorité' 						=> 'T1 Cha +'. round(.5 * $skills['Autorité']),
    'Mana Autorité' 						=> 5,

    'Infos Ténacité des trolls' 			=> 'T1 Con +'. round(.5 * $skills['Ténacité des trolls']),
    'Mana Ténacité des trolls' 				=> 5,

    'Infos Force de colosse' 				=> 'T1 For +'. round(.5 * $skills['Force de colosse']),
    'Mana Force de colosse' 				=> 5,

    // RACES ==================================================================================

    'Infos Pause de midi' 					=> round(1 + .25 * $skills['Pause de midi']).' cibles.grp',
    'Mana Pause de midi' 					=> 4,

    'Infos Marche silencieuse' 				=> '4T ini+'. round(.34 * $skills['Marche silencieuse']).', att emb +'. $skills['Marche silencieuse'].
											', att cac +'. round(.5* $skills['Marche silencieuse']).', att piè +'. round(.34 * $skills['Marche silencieuse']).', att soc -'. $skills['Marche silencieuse'],
    'Mana Marche silencieuse' 				=> 2,

    'Infos Bouclier de pensées' 			=> round(.17 * $niv).' cibles.grp, Eff.ill def.dom.cont,perf,coup,aci,emp,elec,feu,gla,psy +'. 
											round(1 + .25 * $skills['Bouclier de pensées']).'/+'. round(1 + .1 * $skills['Bouclier de pensées']).'/+1, R.PM -'. 
											round(1 + .25 * $skills['Bouclier de pensées']).', Vol +'. round(.1 * $skills['Bouclier de pensées']) .', def mag +'. round(.25 * $skills['Bouclier de pensées']),
    'Mana Bouclier de pensées' 				=> 4,

    'Infos Vue acérée' 						=> '2T ini +'. round(.5 * $skills['Vue acérée']),
    'Mana Vue acérée' 						=> 2,

    'Infos Penseur vif' 					=> '2T ini +'. round(.5 * $skills['Penseur vif']),
    'Mana Penseur vif' 						=> 2,

    'Infos Métamorphose' 					=> 'T1 act=0, def.cac,cad,mag -75%, def.emb,nat -100%, Eff.ill R.PM +'. round(1 + .5 * $skills['Métamorphose']).', R.PV -'. round(2 + .25 * $skills['Métamorphose']),
    'Mana Métamorphose' 					=> 2,

    'Infos Raconter des histoires' 			=> 'tt cibles.pos, 2T def soc +'. round(.75 * $skills['Raconter des histoires']),
    'Mana Raconter des histoires' 			=> 2,

    'Infos Têtu' 							=> '3T def mag +'.round(.5 * $skills['Têtu']).', def soc +'.round(.34 * $skills['Têtu']),
    'Mana Têtu' 							=> 3,

    'Infos Porteur d\'étendard' 			=> round(3 + .34 * $niv).' cibles.grp',
    'Mana Porteur d\'étendard' 				=> 7,

    'Infos Levez vos verres !' 				=> round(1 + .2 * $niv).' cibles.grp',
    'Mana Levez vos verres !' 				=> 3,

	'Infos Ferronnerie'						=> round(.17 * $niv).' cibles.pos, Eff.talents Combat à l\'épée/arme de choc/hache +'.round(1 + .2 * $skills['Ferronnerie']),
	'Mana Ferronnerie'						=> 7,

	'Infos Grande magie runique'			=> round(1 + .25 * $niv).' cibles.pos, Eff.ill R.PM -'.round(2 + .34 * $skills['Grande magie runique']),
	'Mana Grande magie runique'				=> 5,

	'Infos Fumer collectif'					=> round(1 + .2 * $niv).' cibles.grp',
	'Mana Fumer collectif'					=> 4,

	'Infos Pause de midi'					=> round(1 + .25 * $niv).' cibles.grp',
	'Mana Pause de midi'					=> 4,

    'Infos Force de la glace' 				=> 'Eff.ill def.dom gla +'. round(.34 * $skills['Force de la glace']).'/+'. round(.34 * $skills['Force de la glace']).'/+'. round(.34 * $skills['Force de la glace']).
											', att.dom gla +'. round(.25 * $skills['Force de la glace']).'/+'. round(.25 * $skills['Force de la glace']).'/+'. round(.25 * $skills['Force de la glace']).
											', For,Vol +'.round(1 + .2 * $skills['Force de la glace']).', PV +'.round(.75 * $skills['Force de la glace']).', R.PM -'.round(1 + .25 * $skills['Force de la glace']).
											', def cac,cad,emb -75%, def mag,piè -100%, T1 act=0',
    'Mana Force de la glace' 				=> 3,

    'Infos Bénédiction du feu' 				=> round(2 + .2 * $niv).' cibles.grp, 3T def.dom feu +'. round(.34 * $skills['Bénédiction du feu']).'/+'. round(.34 * $skills['Bénédiction du feu']).'/+'. round(.34 * $skills['Bénédiction du feu']).
											', att.dom feu +'. round(.34 * $skills['Bénédiction du feu']).'/+'. round(.34 * $skills['Bénédiction du feu']).'/+'. round(.34 * $skills['Bénédiction du feu']).
											', Adr,Vit +'.round(1 + .34 * $skills['Bénédiction du feu']).', R.PV -'.round(2 + .25 * $skills['Bénédiction du feu']).
											', att cac +'.round(.5 * $skills['Bénédiction du feu']).', def cac +'.round(.5 * $skills['Bénédiction du feu']),
    'Mana Bénédiction du feu' 				=> 5,

	'Infos Subite colère'					=> '2T For +'.round(1 + .2 * $skills['Subite colère']).', Vit +'.round(.2 * $skills['Subite colère']).', R.PV -'.round(.34 * $skills['Subite colère']).
											', att cac +'.round($skills['Subite colère']).', def mag,soc -'.round(.75 * $skills['Subite colère']),
	'Mana Subite colère'					=> '=1',

	'Infos Force de la Terre'				=> 'Eff.ill R.PV +'.round(1 + .67 * $skills['Force de la Terre']).', R.PM -'.round(1 + .25 * $skills['Force de la Terre']).', T1 act=0, '.
											'def cac,cad -25%, def piè,emb -100%',
	'Mana Force de la Terre'				=> 5,

	'Infos Puissance de la Terre'			=> 'Eff.ill For,Vit +'.round(1 + .2 * $skills['Puissance de la Terre']).', R.PV -'.round(.34 * $skills['Puissance de la Terre']).
											', att cac,soc +'.round(.5 * $skills['Puissance de la Terre']).', att cad,mag -'.round(.5 * $skills['Puissance de la Terre']).
											', def mag -'.round(.5 * $skills['Puissance de la Terre']).', def soc +'.round(1 + .2 * $skills['Puissance de la Terre']).', T1 act=0, def cac -75%',
	'Mana Puissance de la Terre'			=> 3,


    // Savoir des anciens
    'Savoir des anciens (offensif)' 		=> round(2 * $att['int'] + $att['vol'] + 2 * $skills['Savoir des anciens (offensif)'] + $bonus['att']['Magie']),
    'Infos Savoir des anciens (offensif)' 	=> round(.1 * $niv).' cibles.grp',
    'Mana Savoir des anciens (offensif)' 	=> 7,

    'Infos Savoir des anciens (défensif)' 	=> round(.1 * $niv).' cibles.grp',
    'Mana Savoir des anciens (défensif)' 	=> 7,

    'Mana Invocation des anciens' 			=> 15,

    'end' 									=> ''
  );

  if (count($matos)) {
    $item_infos = array();
    $bouffe = 0;
    if ($skills['Pause de midi']) {
      $bouffe = $skills['Pause de midi'];
    } elseif ($skills['Il faut d\'abord manger quelque chose !']) {
      $bouffe = $skills['Il faut d\'abord manger quelque chose !'];
    }
    if ($bouffe) {
      $bouffe_infos = array(
        'Pain à l\'ail' => 'cha-'. floor(1 + .34 * $bouffe).', att.soc+'. floor(.34 * $bouffe).' def.mal+'. floor(2 + .5 * $bouffe).' dom.sac nbc +'. floor(1 + .1 * $bouffe),
        'Le strudel de gretel' => 'adr+'. floor(1 + .12 * $bouffe).' for+'. floor(1 + .12 * $bouffe).' PV+'. floor(5 + .5 * $bouffe).' PM+'. floor(2 + .5 * $bouffe),
        'Sac à provisions' => 'PV+'. floor(2 + .25 * $bouffe).' att mag,cac,dist +'. floor(.25 * $bouffe),
        'Miche de pain fait d\'après la vraie recette d\'autrefois' => 'att.mag+'. floor(1 + .34 * $bouffe).' def.mag+'. floor(1 + .25 * $bouffe) .' PM+'. floor(2 + .75 * $bouffe).' cha-1 vol-1',
        'Potée aux haricots' => 'cha-'. floor(1 + .34 * $bouffe).' con+'. floor(.2 * $bouffe).' PV+'. floor(.5 * $bouffe).' att.soc+'. floor(.34 * $bouffe).' def.soc-'. floor(.34 * $bouffe),
        'Escalope de porc' => 'con+'. floor(1 + .1 * $bouffe).' for+'. floor(1 + .1 * $bouffe).' R.PV+'. floor(.34 * $bouffe),
        'Tartine au saucisson' => 'def.soc+'. floor(1 + .75 * $bouffe).' def cac,cad +'. floor(2 + .2 * $bouffe),
        'Tablette de chocolat' => 'cha+'. floor(2 + .25 * $bouffe).' PV+'. floor(1 + .2 * $bouffe).' def soc +'. floor(2 + .1 * $bouffe).' def mal -'. floor(2 + .34 * $bouffe),
        'Bonbons pour la gorge' => 'def mal +'. 3 + $bouffe.' chants de courage, guerison +'. floor(1 + .1 * $bouffe),
        'Soupe aux legumes de mémé Hanna' => '3T: vit-'. floor(1 + .17 * $bouffe).' per-'. floor(1 + .17 * $bouffe).' R.PV-'. floor(.34 * $bouffe).' / ill. def mal +'. floor(5 + 1.5 * $bouffe).' vol+'. floor(1 + .17 * $bouffe),
        'Sachet de grillons kahalad grillés' => 'con+'. floor(1 + .08 * $bouffe).' dex+'. floor(1 + .08 * $bouffe).' R.PV+'. floor(2 + .06 * $bouffe).
                                                ' ini-'. floor(3 + .25 * $bouffe).' att.cac+'. floor(3 + .2 * $bouffe).' def.mag-'. floor(3 + .2 * $bouffe).' def.soc-'. floor(4 + .25 * $bouffe).' sanglier furieux +'. floor(1 + .13 * $bouffe),
        'Miel congelé sur un bâton' => 'PM+'. floor(2 + .25 * $bouffe).' PV+'. floor(2 + .25 * $bouffe).' cha+'. floor(.1 * $bouffe).' con+1 def.mal+'. floor(.34 * $bouffe).' def.mag+6',
        'carottes bouillies' => 'per+'. floor(1 + .1 * $bouffe).' Oeil d\'aigle +'. floor(1 + .2 * $bouffe),
        'Dattes (séchées)' => 'PV+'. floor(4 + .75 * $bouffe).' MP+'. floor(2 + .5 * $bouffe).' def mal -'. floor(1 + .1 * $bouffe),
        'Crackers au fromage de Gwendolin (miettes)' => 'dex+1 vit+1 PV+'. floor(2 + .25 * $bouffe).' ini+3 def mal -50%',

        // ?
        'Goyave rousse' => '',
        
        // bouffe 6 : effet Prendre une potion guérissante +2, talent Liberté du fou +2, charisme +1, initiative -3 , défense Socialement +4, adresse -1 , défense Maladie -5
        // bouffe 5 : Pause de midi -25(%). Effet Prendre une potion guérissante +1, Liberté du fou +1 rang, charisme +1, défense social +3. Adresse -1, défense maladie -5.
        // bouffe 3 : effet Prendre une potion guérissante +1, talent Liberté du fou +1, charisme +1, défense Socialement +2, défense Maladie -5
        'Sucette géante' => '',

        'end' => ''
      );
      $item_infos = array_merge($item_infos,$bouffe_infos);
    }
    $boire = 0;
    if ($skills['Levez vos verres !']) {
      $boire = $skills['Levez vos verres !'];
    } elseif ($skills['Boire']) {
      $boire = $skills['Boire'];
    }
    if ($boire) {
      $boire_infos = array(
        'Gourde' => '2T def cac,cad +'. round(.2 * $boire).' PV+3 R.PM+1',
        'Bouteille d\'eau de vie de Gustave' => 'dex+'. round(1 + .17 * $boire). 'per-1 str+'. round(1 + .2 * $boire). 
                                                ' PV+'. round(1 + .25 * $boire).' R.PM-1 att cac+'. round(3 + .34 * $boire).' cad,mag -4 def cac,cad,mag -10%',

        'Bouteille de bière brune' => '',
        'Bouteille de bière' => '',
        'Bouteille de Kestrar' => '',
        'Seau plein d\'airgnas glacé' => '',
        'Potion bouillonnante de résistance à la magie' => '',
        'Bouillon de Mandragore' => '',
        'Jus de grenade' => '',
        'Lait chaud au miel' => '',
        'Jus de citrouille' => '',
        'Vin chaud' => '',
        'Tonneau de l\'eau de vie' => '',
        'Extrait de réglisse concentrée (petit pot)' => '',

        'end' => ''
      );
      $item_infos = array_merge($item_infos,$boire_infos);
    }
    $fume = 0;
    if ($skills['Fumer collectif']) {
      $fume = $skills['Fumer collectif'];
    } elseif ($skills['Fumer la pipe']) {
      $fume = $skills['Fumer la pipe'];
    }
    if ($fume) {
      $fume_infos = array(
        'Tabac contenant des éclats de réglisse (sachet)' => 'PM+'. round(3 + .2 * $fume). ' R.PM+'. round(1 + .2 * $fume),
        'Tabac à pipe (sachet)' => 'PM+'. round(3 + .17 * $fume).' PV+'. round(3 + .17 * $fume).' def nat +'. round(1 + .2 * $fume),
        'Tabac à pipe ordinaire (sachet)' => 'def soc +'. round(1 + .34 * $fume),
        'Tabac avec Marie-Jeanne (sachet)' => 'def soc +'. (3 + $fume).' ini-'. (3 + $fume).' Transe profonde +'. round(.1 * $fume).' Transe légère +'. round(.1 * $fume),
        'Bon tabac à pipe (sachet)' => 'def soc +'. round(2 + .5 * $fume).' vol+1 R.PM+'. round(.17 * $fume),

        'Tabac à pipe noir (sachet)' => '',

        'Tabac à pipe noir (séléctionné) (sachet)' => '',
        
        'Tabac à pipe avec du taxon séché (sachet)' => '',
        'tabac de larosée (sachet)' => '',
        'Tabac lassé de feuilles d\'Igonous (sachet)' => '',

        'end' => ''
      );
      $item_infos = array_merge($item_infos,$fume_infos);
    }
  }

}



?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="SHORTCUT ICON" href="WOD.gif" type="image/gif" /> 
<title>Wodificator!</title>
<style>
.error {
	widht:90%;
	text-align:center;
	color:red;
	font-weight:bold;
}
a:link {
	color: #FF9F00;
	text-decoration: none;
}
a:hover {
	color: black;
	text-decoration: none;
}
a:active {
	color: black;
	text-decoration: none;
}
</style>
<script>
function show_hide (id) {
	var div = document.getElementById(id);
	if (div.style.display == 'none')
		div.style.display = '';
	else 
		div.style.display = 'none';
}

var sep = "|";
var arr_list = new Array(<?php echo '"'.implode('","', $list).'"'; ?>);
var arr_type = new Array("p","a");
var arr_type2 = new Array("_bf","_p");

function recup_bonus_eq() {
	var str_bonus_eq = "";
	for (var i = 0; i < arr_list.length; i++) {
		for (var j = 0; j < arr_type.length; j++) {
			for (var k = 0; k < arr_type2.length; k++) {
				var lst_bonus = document.getElementById(arr_type[j] + arr_list[i] + arr_type2[k]);
				str_bonus_eq += lst_bonus.value + sep;
			}
		}
	}
	document.getElementById('lst_eq').value = str_bonus_eq;
}

function inser_bonus_eq() {
	if (document.getElementById('lst_eq').value != '') {
		var lst_bonus = document.getElementById('lst_eq').value;
		var arr_lst_bonus = lst_bonus.split(sep);
		if(arr_lst_bonus.length == (arr_list.length * arr_type.length * arr_type2.length) +1) {
			var m = 0;
			for (var i = 0; i < arr_list.length; i++) {
				for (var j = 0; j < arr_type.length; j++) {
					for (var k = 0; k < arr_type2.length; k++) {
						var lst_bonus = document.getElementById(arr_type[j] + arr_list[i] + arr_type2[k]);
						lst_bonus.value = arr_lst_bonus[m];
						m++;
					}
				}
			}
		} else {
			alert("La ligne de bonus que vous avez donné n'est pas bonne");
		} 
	}
}
</script>
</head>
<body>

<?php if (!$msg_err && count($out)) { ?> 
<div style="font-family:monospace;">
<?php
/* Compteur */
$compteur++;
$fp = fopen("compteur.txt","w");
fputs($fp, $compteur, strlen($compteur));
fclose($fp); // On ferme le fichier


/*
  foreach ($out as $a=>$o) {
    echo "$a : ".$o."<br/>\n";
  }
*/
  // echo '---------------------------------------------------------- ' ."<br />\n";
  echo '[table][tr][td][h1][hero:'. $out['name'] .'][/h1][/td][td]'.($out['title']?'[h2][color=#FFCF00], '. $out['title'].'[/color][/h2]':'').'[/td][/tr][/table]';
  echo "[font=monospace]";
  echo "<br />\n";
  echo '[class:'. $out['race'] .'] [class:'. $out['class'] .'] niv. '. $out['niveau'];
  //if ($out['monde']) echo ', sur '. $out['monde'];
  echo "<br />\n";
  echo "<br />\n";
  echo 'force ............. '. $out['for'] ."<br />\n";
  echo 'constitution ...... '. $out['con'] ."<br />\n";
  echo 'intelligence ...... '. $out['int'] ."<br />\n";
  echo 'adresse ........... '. $out['adr'] ."<br />\n";
  echo 'charisme .......... '. $out['cha'] ."<br />\n";
  echo 'vitesse ........... '. $out['vit'] ."<br />\n";
  echo 'perception ........ '. $out['per'] ."<br />\n";
  echo 'force de volonté .. '. $out['vol'] ."<br />\n";
  echo "<br />\n";
  printf('points de vie ..... %7s / régénération ... %s<br />',$out['hp'],$out['regen hp']);
  printf('points de mana .... %7s / régénération ... %s<br />',$out['mp'],$out['regen mp']);
  echo 'Actions ........... '. $out['actions'] ."<br />\n";
  echo 'Bonus initiative .. '. $out['bonus initiative'] ."<br />\n";
  echo 'Score initiative .. '. $out['total initiative'] ."<br />\n";
  echo "<br />\n";
  if (is_array($armor)) {
    foreach ($armor as $n=>$v) {
      echo utf8_sprintf("%'.-22s %'.-10s %s",$v[0],$v[1],$v[2]);
      echo "<br />\n";
    }
  }

  if (count($skills)) {
    echo "<br />\n";
    krsort($order);
    foreach ($order as $v=>$s) {
      foreach ($s as $n) {
        if ($out[$n] or $_POST['fulldisplay'] == 'on') {
          if ($_POST['fulltalents'] == 'on' and $stat_skills["Infos $n"] and $out[$n]) {
            printf("%'.-9s [skill:%s]  [i][color=#aaaaaa](%s)[/color][/i]",$out[$n],$n,$stat_skills["Infos $n"]);
          } else {
            printf("%'.-9s [skill:%s]",$out[$n],$n);
          }
          if ($stat_skills["Mana $n"] and $out[$n]) {
            $b = $stat_skills["Mana $n"];
            if (substr($b,0,1) == '=') {
              $mana = substr($b,1);
            } else {
              $mana = floor(.9 * $b + ($b * .1 * ($skills[$n] - 1))); 
            }
            echo ' ... M [b]'.$mana.'[/b]';
          }
          echo "<br />\n";
        }
      }
    }
    echo "<br />[color=#FFCF00]~Parades~[/color]<br />\n";
        echo disp('Éviter un coup',true);
        echo disp('Éviter un tir',true);
        echo disp('Résistance à la magie',true);
        echo disp('Résistance sociale',true);
        echo disp('Résistance force de la nature',true);
        echo disp('Résistance embuscade',true);
        echo disp('Résistance décl. piège',true);
        echo disp('Résistance maladie',true);
        echo disp('Résistance explosion ou souffle',true);
        echo disp('Résistance désorientation',true);
        echo disp('Résistance effroi',true);
        echo disp('Résistance tir de précision',true);
        echo disp('Résistance immobilisation',true);

    if ($skills['Confiance divine'] || $skills['Inflexible'] || $skills['Liberté du fou'] || $skills['Entêtement du bœuf'] || $skills['Calme stoïque'] || $skills['Discipline']) 
		echo "<br />[color=#000]  * Parade Sociale (avec talent):[/color]<br />\n";
		
        echo disp('Confiance divine');
        echo disp('Inflexible');
        echo disp('Liberté du fou');
        echo disp('Entêtement du bœuf');
        echo disp('Calme stoïque');
        echo disp('Discipline');

	if ($skills['Parade au bouclier'] || $skills['Combat à la hache'] || $skills['Combat à l\'épée'] || $skills['Combat à l\'arme de choc'] || $skills['Combat à l\'arme d\'hast'] ||
		$skills['Combat au couteau'] || $skills['Combat à mains nues'] || $skills['Combat au bâton'] || $skills['Escrime'] || $skills['Main griffue']) 
		echo "<br />[color=#000]  * Parades CaC (bouclier & armes):[/color]<br />\n";
		
        echo disp('Parade au bouclier');
        if ($skills['Combat à la hache']) echo disp('Parade Combat à la hache',true);
        if ($skills['Combat à l\'épée']) echo disp('Parade Combat à l\'épée',true);
        if ($skills['Combat à l\'arme de choc']) echo disp('Parade Combat à l\'arme de choc',true);
        if ($skills['Combat à l\'arme d\'hast']) echo disp('Parade Combat à l\'arme d\'hast',true);
        if ($skills['Combat au couteau']) echo disp('Parade Combat au couteau',true);
        if ($skills['Combat à mains nues']) echo disp('Parade Combat à mains nues',true);
        if ($skills['Combat au bâton']) echo disp('Parade Combat au bâton',true);
        if ($skills['Escrime']) echo disp('Parade Escrime',true);
        if ($skills['Main griffue']) echo disp('Parade Main griffue',true);

    if ($skills['Attaque rapide'] || $skills['Vigilance'] || $skills['Pied léger'] || $skills['Vue d\'ensemble'] || $skills['Zèle']) 
		echo "<br />[color=#000]  * Init (avec talent):[/color]<br />\n";

        echo disp('Attaque rapide');
        echo disp('Vigilance');
        echo disp('Pied léger');
        echo disp('Vue d\'ensemble');
        echo disp('Zèle');


    echo "<br />[color=#FFCF00]~Attaques~[/color]\n";

    if ($skills['Combat à l\'épée'] || $skills['Combat à la hache'] || $skills['Combat à l\'arme de choc'] || $skills['Combat à l\'arme d\'hast'] || $skills['Combat au couteau'] ||
    $skills['Poignarder'] || $skills['Combat à mains nues'] || $skills['Combat au bâton'] || $skills['Escrime'] || $skills['Main griffue'] ||
    $skills['Coup d\'estoc'] || $skills['Double touche'] || $skills['Coup circulaire'] || $skills['Toucher magique'] || $skills['Double coup'] ||
    $skills['Poings volants'] || $skills['Châtiment'] || $skills['Attaque perturbante'] || $skills['Désarmement'])
		echo "<br />[color=#000]  * CaC :[/color]<br />\n";

		if ($skills['Combat à l\'épée'])               echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Combat à l\'épée', $stat_skills['Attaque Combat à l\'épée'], $stat_skills['Effet Combat à l\'épée'])   ."<br />\n";
		if ($skills['Combat à la hache'])              echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Combat à la hache', $stat_skills['Attaque Combat à la hache'], $stat_skills['Effet Combat à la hache'])   ."<br />\n";
		if ($skills['Combat à l\'arme de choc'])       echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Combat à l\'arme de choc', $stat_skills['Attaque Combat à l\'arme de choc'], $stat_skills['Effet Combat à l\'arme de choc'])   ."<br />\n";
		if ($skills['Combat à l\'arme d\'hast'])       echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Combat à l\'arme d\'hast', $stat_skills['Attaque Combat à l\'arme d\'hast'], $stat_skills['Effet Combat à l\'arme d\'hast'])   ."<br />\n";
		if ($skills['Combat au couteau'])              echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Combat au couteau', $stat_skills['Attaque Combat au couteau'], $stat_skills['Effet Combat au couteau'])   ."<br />\n";
		if ($skills['Poignarder'])                     echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Poignarder', $stat_skills['Attaque Poignarder'], $stat_skills['Effet Poignarder'])   ."<br />\n";
		if ($skills['Combat à mains nues'])            echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Combat à mains nues', $stat_skills['Attaque Combat à mains nues'], $stat_skills['Effet Combat à mains nues'])   ."<br />\n";
		if ($skills['Combat au bâton'])                echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Combat au bâton', $stat_skills['Attaque Combat au bâton'], $stat_skills['Effet Combat au bâton'])   ."<br />\n";
		if ($skills['Escrime'])                        echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Escrime', $stat_skills['Attaque Escrime'], $stat_skills['Effet Escrime'])   ."<br />\n";
		if ($skills['Main griffue'])                   echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Main griffue', $stat_skills['Attaque Main griffue'], $stat_skills['Effet Main griffue'])   ."<br />\n";
		if ($skills['Coup d\'estoc'])                  echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Coup d\'estoc', $stat_skills['Attaque Coup d\'estoc'], $stat_skills['Effet Coup d\'estoc'])   ."<br />\n";
		if ($skills['Double touche'])                  echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Double touche', $stat_skills['Attaque Double touche'], $stat_skills['Effet Double touche'])   ."<br />\n";
		if ($skills['Coup circulaire'])                echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Coup circulaire', $stat_skills['Attaque Coup circulaire'], $stat_skills['Effet Coup circulaire'])   ."<br />\n";
		if ($skills['Toucher magique'])                echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Toucher magique', $stat_skills['Attaque Toucher magique'], $stat_skills['Effet Toucher magique'])   ."<br />\n";
		if ($skills['Double coup'])                    echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Double coup', $stat_skills['Attaque Double coup'], $stat_skills['Effet Double coup'])   ."<br />\n";
		if ($skills['Poings volants'])                 echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Poings volants', $stat_skills['Attaque Poings volants'], $stat_skills['Effet Poings volants'])   ."<br />\n";
		if ($skills['Châtiment'])                      echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Châtiment', $stat_skills['Attaque Châtiment'], $stat_skills['Effet Châtiment'])   ."<br />\n";
		echo disp('Attaque perturbante');
		echo disp('Désarmement');

	if ($skills['Tir à l\'arc'] || $skills['Tir de précision à l\'arc'] || $skills['Double tir'] || $skills['Pluie de flèches'] || $skills['Tir à la fronde'] ||
	$skills['Pierre de David'] || $skills['Pierre de Frumol'] || $skills['Tir à l\'arbalète'] || $skills['Tir de précision à l\'arbalète'] || $skills['Tir transperçant'] ||
	$skills['Artificier'] || $skills['Tir au pistolet'] || $skills['Tir de précision au pistolet'] || $skills['Pluie de balles'] || $skills['Armes de jet'] ||
	$skills['Lancer de filet'] || $skills['Marquer sa proie'])
		echo "<br />[color=#000]  * CaD :[/color]<br />\n";

		if ($skills['Tir à l\'arc'])                   echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Tir à l\'arc', $stat_skills['Attaque Tir à l\'arc'], $stat_skills['Effet Tir à l\'arc'])   ."<br />\n";
		if ($skills['Tir de précision à l\'arc'])      echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Tir de précision à l\'arc', $stat_skills['Attaque Tir de précision à l\'arc'], $stat_skills['Effet Tir de précision à l\'arc'])   ."<br />\n";
		if ($skills['Double tir'])                     echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Double tir', $stat_skills['Attaque Double tir'], $stat_skills['Effet Double tir'])   ."<br />\n";
		if ($skills['Pluie de flèches'])               echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Pluie de flèches', $stat_skills['Attaque Pluie de flèches'], $stat_skills['Effet Pluie de flèches'])   ."<br />\n";
		if ($skills['Tir à la fronde'])                echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Tir à la fronde', $stat_skills['Attaque Tir à la fronde'], $stat_skills['Effet Tir à la fronde'])   ."<br />\n";
		if ($skills['Pierre de David'])           	   echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Pierre de David', $stat_skills['Attaque Pierre de David'], $stat_skills['Effet Pierre de David'])   ."<br />\n";
		if ($skills['Pierre de Frumol'])               echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Pierre de Frumol', $stat_skills['Attaque Pierre de Frumol'], $stat_skills['Effet Pierre de Frumol'])   ."<br />\n";
		if ($skills['Tir à l\'arbalète'])              echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Tir à l\'arbalète', $stat_skills['Attaque Tir à l\'arbalète'], $stat_skills['Effet Tir à l\'arbalète'])   ."<br />\n";
		if ($skills['Tir de précision à l\'arbalète']) echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Tir de précision', $stat_skills['Attaque Tir de précision à l\'arbalète'], $stat_skills['Effet Tir de précision à l\'arbalète'])   ."<br />\n";
		if ($skills['Tir transperçant'])               echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Tir transperçant', $stat_skills['Attaque Tir transperçant'], $stat_skills['Effet Tir transperçant'])   ."<br />\n";
		if ($skills['Artificier'])                     echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Artificier', $stat_skills['Attaque Artificier'], $stat_skills['Effet Artificier'])   ."<br />\n";
		if ($skills['Tir au pistolet'])                echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Tir au pistolet', $stat_skills['Attaque Tir au pistolet'], $stat_skills['Effet Tir au pistolet'])   ."<br />\n";
		if ($skills['Tir de précision au pistolet'])   echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Tir de précision au pistolet', $stat_skills['Attaque Tir de précision au pistolet'], $stat_skills['Effet Tir de précision au pistolet'])   ."<br />\n";
		if ($skills['Pluie de balles'])                echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Pluie de balles', $stat_skills['Attaque Pluie de balles'], $stat_skills['Effet Pluie de balles'])   ."<br />\n";
		if ($skills['Armes de jet'])                   echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Armes de jet', $stat_skills['Attaque Armes de jet'], $stat_skills['Effet Armes de jet'])   ."<br />\n";
		echo disp('Lancer de filet');
		echo disp('Marquer sa proie');

	if ($skills['Flèche d\'énergie'] || $skills['Orage d\'énergie'] || $skills['Tempête d\'énergie'] || $skills['Flèche de feu'] || $skills['Boule de feu'] ||
	$skills['Pluie de feu'] || $skills['Tir de glace'] || $skills['Nuage de glace'] || $skills['Pluie de glace'] || $skills['Retrait de mana'] ||
	$skills['Paroles de pouvoir'] || $skills['Sortilège offensif'] || $skills['Feu sacré'] || $skills['Feu infernal'] || $skills['Marque magique'] || 
	$skills['Petite magie noire'] || $skills['Grande magie noire'] || $skills['Toucher d\'Akbeth'] ||
	$skills['Jugement d\'Akbeth'] || $skills['Don : provoquer de l\'angoisse'] || $skills['Don : jet de glace mortel'])
		echo "<br />[color=#000]  * Magie :[/color]<br />\n";

		if ($skills['Flèche d\'énergie'])              echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Flèche d\'énergie', $stat_skills['Attaque Flèche d\'énergie'], $stat_skills['Effet Flèche d\'énergie'])   ."<br />\n";
		if ($skills['Orage d\'énergie'])               echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Orage d\'énergie', $stat_skills['Attaque Orage d\'énergie'], $stat_skills['Effet Orage d\'énergie'])   ."<br />\n";
		if ($skills['Tempête d\'énergie'])             echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Tempête d\'énergie', $stat_skills['Attaque Tempête d\'énergie'], $stat_skills['Effet Tempête d\'énergie'])   ."<br />\n";
		if ($skills['Flèche de feu'])                  echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Flèche de feu', $stat_skills['Attaque Flèche de feu'], $stat_skills['Effet Flèche de feu'])   ."<br />\n";
		if ($skills['Boule de feu'])                   echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Boule de feu', $stat_skills['Attaque Boule de feu'], $stat_skills['Effet Boule de feu'])   ."<br />\n";
		if ($skills['Pluie de feu'])                   echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Pluie de feu', $stat_skills['Attaque Pluie de feu'], $stat_skills['Effet Pluie de feu'])   ."<br />\n";
		if ($skills['Tir de glace'])                   echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Tir de glace', $stat_skills['Attaque Tir de glace'], $stat_skills['Effet Tir de glace'])   ."<br />\n";
		if ($skills['Nuage de glace'])                 echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Nuage de glace', $stat_skills['Attaque Nuage de glace'], $stat_skills['Effet Nuage de glace'])   ."<br />\n";
		if ($skills['Pluie de glace'])                 echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Pluie de glace', $stat_skills['Attaque Pluie de glace'], $stat_skills['Effet Pluie de glace'])   ."<br />\n";
		if ($skills['Retrait de mana'])                echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Retrait de mana', $stat_skills['Attaque Retrait de mana'], $stat_skills['Effet Retrait de mana'])   ."<br />\n";
		if ($skills['Paroles de pouvoir'])             echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Paroles de pouvoir', $stat_skills['Attaque Paroles de pouvoir'], $stat_skills['Effet Paroles de pouvoir'])   ."<br />\n";
		if ($skills['Sortilège offensif'])             echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Sortilège offensif', $stat_skills['Attaque Sortilège offensif'], $stat_skills['Effet Sortilège offensif'])   ."<br />\n";

		if ($skills['Feu sacré'])                      echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Feu sacré', $stat_skills['Attaque Feu sacré'], $stat_skills['Effet Feu sacré'])   ."<br />\n";
		if ($skills['Feu infernal'])                   echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Feu infernal', $stat_skills['Attaque Feu infernal'], $stat_skills['Effet Feu infernal'])   ."<br />\n";

		echo disp('Marque magique');
		echo disp('Petite magie noire');
		echo disp('Grande magie noire');
		echo disp('Toucher d\'Akbeth');
		echo disp('Jugement d\'Akbeth');

		if ($skills['Don : provoquer de l\'angoisse']) echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Don : provoquer de l\'angoisse', $stat_skills['Attaque Don : provoquer de l\'angoisse'], $stat_skills['Effet Don : provoquer de l\'angoisse'])   ."<br />\n";
		if ($skills['Don : jet de glace mortel'])      echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Don : jet de glace mortel', $stat_skills['Attaque Don : jet de glace mortel'], $stat_skills['Effet Don : jet de glace mortel'])   ."<br />\n";

	if ($skills['Exorcisme'] || $skills['Grand exorcisme'])
		echo "<br />[color=#000]  * Bannissement :[/color]<br />\n";

		if ($skills['Exorcisme'])                      echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Exorcisme', $stat_skills['Attaque Exorcisme'], $stat_skills['Effet Exorcisme'])   ."<br />\n";
		if ($skills['Grand exorcisme'])                echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Grand Exorcisme', $stat_skills['Attaque Grand exorcisme'], $stat_skills['Effet Grand exorcisme'])   ."<br />\n";

	if ($skills['Tir à la sarbacane'] || $skills['Tir à répétition'])
		echo "<br />[color=#000]  * Tir de précision :[/color]<br />\n";

		echo disp('Tir à la sarbacane');
		echo disp('Tir à répétition');

	if ($skills['Grêlon'] || $skills['Grêle'] || $skills['Don : maître des racines'])
		echo "<br />[color=#000]  * FdN :[/color]<br />\n";

		if ($skills['Poignard de terre'])              echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Poignard de terre', $stat_skills['Attaque Poignard de terre'], $stat_skills['Effet Poignard de terre'])   ."<br />\n";
		if ($skills['Grêlon'])                         echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Grêlon', $stat_skills['Attaque Grêlon'], $stat_skills['Effet Grêlon'])   ."<br />\n";
		if ($skills['Grêle'])                          echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Grêle', $stat_skills['Attaque Grêle'], $stat_skills['Effet Grêle'])   ."<br />\n";
		echo disp('Don : maître des racines');

	if ($skills['Malédiction de Rashon'])
		echo "<br />[color=#000]  * Maladie :[/color]<br />\n";

		if ($skills['Malédiction de Rashon'])          echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Malédiction de Rashon', $stat_skills['Attaque Malédiction de Rashon'], $stat_skills['Effet Malédiction de Rashon'])   ."<br />\n";

	if ($skills['Appel de la hyène'] || $skills['Rire de la hyène'] || $skills['Don : maître des animaux sauvages'])
		echo "<br />[color=#000]  * Effroi :[/color]<br />\n";

		echo disp('Appel de la hyène');
		echo disp('Rire de la hyène');
		if ($skills['Don : maître des animaux sauvages']) echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Don : maître des animaux sauvages', $stat_skills['Attaque Don : maître des animaux sauvages'], $stat_skills['Effet Don : maître des animaux sauvages'])   ."<br />\n";

	if ($skills['Don : ondes et fumée'] || $skills['Don : explosif'] || $skills['Don : utilisation alternative de poudre noire'])
		echo "<br />[color=#000]  * Explosion :[/color]<br />\n";

		if ($skills['Don : utilisation alternative de poudre noire'])  echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Don : utilisation alternative de poudre noire', $stat_skills['Attaque Don : utilisation alternative de poudre noire'], $stat_skills['Effet Don : utilisation alternative de poudre noire'])   ."<br />\n";
		if ($skills['Don : explosif'])                 echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Don : explosif', $stat_skills['Attaque Don : explosif'], $stat_skills['Effet Don : explosif'])   ."<br />\n";
		if ($skills['Don : ondes et fumée'])           echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Don : ondes et fumée', $stat_skills['Attaque Don : ondes et fumée'], $stat_skills['Effet Don : ondes et fumée'])   ."<br />\n";


	if ($skills['Hurlement guerrier'] || $skills['Se moquer'] || $skills['Chant de dérision'] || $skills['Grand chant de dérision'] || $skills['Personnage imposant'] ||
	$skills['Apparence impressionnante'] || $skills['Masque illusoire'] || $skills['Regard sévère'] || $skills['Faire des grimaces'] || $skills['Entrée en scène audacieuse'] ||
	$skills['Bonne réputation'] || $skills['Intimider'] || $skills['Regard du cobra'] || $skills['Condamner'] || $skills['Don : inspire le respect'] ||
	$skills['Don : sourire gagnant'] || $skills['Don : accaparer l\'attention'] || $skills['Don : cri de perdition'] || $skills['Don : malédiction du froid'] || $skills['Don : malédiction de la chaleur'])
		echo "<br />[color=#000]  * Sociales:[/color]<br />\n";

        echo disp('Hurlement guerrier');
        echo disp('Se moquer');
        echo disp('Chant de dérision');
        echo disp('Grand chant de dérision');
        echo disp('Personnage imposant');
        echo disp('Apparence impressionnante');
        echo disp('Masque illusoire');
        echo disp('Regard sévère');
        echo disp('Faire des grimaces');
        echo disp('Entrée en scène audacieuse');
        echo disp('Bonne réputation');
        echo disp('Intimider');
        echo disp('Regard du cobra');
        echo disp('Condamner');
        echo disp('Don : inspire le respect');
		echo disp('Don : sourire gagnant');
		echo disp('Don : accaparer l\'attention');
		echo disp('Don : cri de perdition');
		if ($skills['Don : malédiction du froid'])     echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Don : malédiction du froid', $stat_skills['Attaque Don : malédiction du froid'], $stat_skills['Effet Don : malédiction du froid'])   ."<br />\n";
		if ($skills['Don : malédiction de la chaleur'])echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Don : malédiction de la chaleur', $stat_skills['Attaque Don : malédiction de la chaleur'], $stat_skills['Effet Don : malédiction de la chaleur'])   ."<br />\n";

	if ($skills['Pistage'] || $skills['Intuition'] || $skills['Illumination'] || $skills['Instinct de survie'] || $skills['Traque']) 
		echo "<br />[color=#000]  * Orientation:[/color]<br />\n";

		if ($skills['Pistage'])                        echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Pistage', $stat_skills['Attaque Pistage'], $stat_skills['Effet Pistage'])   ."<br />\n";
		if ($skills['Intuition'])                      echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Intuition', $stat_skills['Attaque Intuition'], $stat_skills['Effet Intuition'])   ."<br />\n";
		if ($skills['Illumination'])                   echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Illumination', $stat_skills['Attaque Illumination'], $stat_skills['Effet Illumination'])   ."<br />\n";
		if ($skills['Instinct de survie'])             echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Instinct de survie', $stat_skills['Attaque Instinct de survie'], $stat_skills['Effet Instinct de survie'])   ."<br />\n";
		if ($skills['Traque'])                         echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Traque', $stat_skills['Attaque Traque'], $stat_skills['Effet Traque'])   ."<br />\n";

	if ($skills['Désamorcer des pièges']) 
		echo "<br />[color=#000]  * Pièges:[/color]<br />\n";

		if ($skills['Désamorcer des pièges'])          echo utf8_sprintf("%'.-35s %'.-8s (effet: %s)",'Désamorcer des pièges', $stat_skills['Attaque Désamorcer des pièges'], $stat_skills['Effet Désamorcer des pièges'])   ."<br />\n";

	if ($skills['Embuscade'] || $skills['Manoeuvre sournoise']) 
		echo "<br />[color=#000]  * Embuscade:[/color]<br />\n";
        echo disp('Embuscade');
        echo disp('Manoeuvre sournoise');
		
	if ($skills['Savoir des anciens (offensif)']) 
		echo "<br />[color=#000]  * Savoir des anciens:[/color]<br />\n";

		echo disp('Savoir des anciens (offensif)');
		
	if ($skills['Prendre une potion curative'] || $skills['Prendre une potion de mana'] || $skills['Premiers secours'] || $skills['Guérison alchimique'] || $skills['Régénération alchimique'] ||
		$skills['Guérison alchimique de masse'] || $skills['Chant de guérison'] || $skills['Grand chant de guérison'] || $skills['Paroles apaisantes'] || $skills['Imposition des mains']) 
			echo "<br />[color=#FFCF00]~Soins~[/color]<br />\n";
        echo disp('Prendre une potion curative');
        echo disp('Prendre une potion de mana');
        echo disp('Premiers secours');
        echo disp('Guérison alchimique');
        echo disp('Régénération alchimique');
        echo disp('Guérison alchimique de masse');
        echo disp('Chant de guérison');
        echo disp('Grand chant de guérison');
        echo disp('Paroles apaisantes');
        echo disp('Imposition des mains');

    echo "<br />\n";
  }

  if (count($matos)) {
    echo "<br />\n";
    //echo "[size=14][b]Matos[/b][/size]<br />\n";
    foreach ($matos as $loc => $n) {
		echo "<br />[o]".$loc.'[/o]';
		foreach ($n as $m) {
			echo "<br />\n[*] [item:".$m."]";
			if ($item_infos[$m]) echo ' [i][color=#aaaaaa]('.$item_infos[$m[0]].')[/color][/i]';
		}
		if ($loc == "Sac à dos" && count($arr_cons) > 0) {
			echo "<br />\n";
			foreach ($arr_cons as $k => $v) {
				echo "<br />\n[*] [item:".$k."] (x".$v.")";
				if ($item_infos[$k]) echo ' [i][color=#aaaaaa]('.$item_infos[$k[0]].')[/color][/i]';
			}
		}
		echo "<br />\n";
    }
    echo "<br />";
  }

  echo '[/font]';
  echo '<br />[i]-- [url='.$_SERVER['HTTP_REFERER'].']wodified[/url] [size=9]v. '. $version .'[/size] le '. date('d/m/Y') ." -- [/i]<br />\n";
?>
</div>

<?php } else { 

if (!$prod) echo '<div style="background-color:#ffccaa;padding:5px;">development version <b>'. $version .'</b></div>';
?>
<div style="width:100%; margin:0; text-align:center;"><img border="0" width="304px" alt="Wodificator" src="wodificator.png"></div>
<div class="error"><?php echo count($msg_err) > 0?"Erreur: la ".implode(" et la ", $msg_err). " ".(count($msg_err) > 1?"sont manquantes":"est manquante"):""; ?></div>
<a href="javascript:show_hide('msg_info'); show_hide('form');">Cliquez ici afficher/cacher les dernières infos</a>
<div id="msg_info" style="display:none;border:1px black solid; padding: 1em; margin-top:1em;">
Voici la nouvelle version du script.<br />
<br />
Tous les talents de classe, de race ainsi que les dons ont été ajouté, à part qqes exceptions (transe, et je ne sais plus quoi d'autre)<br />
Ce fut un travail fastidieux, mais qui je suis sûr sera apprécié par tous.<br />
<br />
Maintenant, il est plus que probable que qqes erreurs se soient glissées au milieu de tout ça.<br />
Comme il m'est difficile de tester tous les talents (sans parler du temps que ça prendrait), je vous propose, ces prochains temps, de bien vérifier <br />
la fiche générée, de regarder si tous vos talents sont bien présents et si les valeurs correspondent à vos calculs.<br />
C'est peu de temps pour vous car vous connaissez déjà bien votre perso.<br />
<br />
Si vous voyez des erreurs ou si vous avez des doutes, venez en parler sur le post du forum (section "La Place publique -> La Bibliothèque des Scripts")<br />
<br />
Cela permettra d'arriver à obtenir le min d'erreurs possibles, ce qui profitera à tous.<br />
<br />
L'autre point, c'est que j'ai l'impression que les infos notées derrière chaque talent peuvent s'avérer trop lourdes/longues telles que je les ai écrites.<br />
Certains talents/dons ont vraiment bcp d'effets différents et il est difficile de réduire tout ça sur une petite longueur tout en restant compréhensible.<br />
<br />
Si vous voyez des infos inutiles et/ou vous avez des idées pour améliorer la lisibilité, n'hésitez pas en en faire part.<br />
<br />
Voilà.<br />
<br />
Profitez bien du script.. Et priez Demosan qu'on ne change plus trop les talents et dons, ou du moins avant looonnngtemps ;)
</div>
<form method="post">
<div id="form" style="padding: 0em; margin:0em; margin-top:1em;">
<input type="submit" value="Envoyer" />
<br />
<br/>
<b>La classe et la race ne sont plus retrouvées automatiquement. Il faut les sélectionner.</b>
<div style="padding:4px;border:1px solid #999999;">
Nom: <input class="text" name="name" size="20" value="<?php echo $_POST['name']; ?>"/> 
Race:
<select name="race">
<option value=""></option>
<?php foreach ($races as $r) echo '<option value="'.$r.'" '.($r==$race?"selected":"").'>'.$r.'</option>'; ?>
</select>
Classe:
<select name="class">
<option value=""></option>
<?php foreach ($classes as $c) echo '<option value="'.$c.'" '.($c==$class?"selected":"").'>'.$c.'</option>'; ?>
</select>
<!--
Monde:
<select name="monde">
<option value=""></option>
<?php foreach ($mondes as $m) echo '<option value="'.$m.'" '.($m=="Ezantoh"?"selected":"").'>'.$m.'</option>'; ?>
</select>
-->
</div>
<br />
Copiez-collez ici la page des <b>attributs</b> (ctrl-a puis ctrl-c).
<br/> Y en a qui n'ont quand même pas honte
<textarea name="attributes" cols="62" rows="6" wrap="soft" style="width:100%;"><?php echo $_POST['attributes']; ?>
</textarea>
<br />
<br />
<span style="float:right;"><input type="checkbox" name="graf" id="graf" value="on" checked="checked" /> <label for="graf">Afficher une gauge</label></span>
<span style="float:right;"><input type="checkbox" name="fulldisplay" id="fulldisplay" value="on" /> <label for="fulldisplay">Afficher les talents non appris</label></span>
<span style="float:right;"><input type="checkbox" name="fulltalents" id="fulltalents" value="on" checked="checked" /> <label for="fulltalents">Afficher les détails des talents</label></span>
Copiez-collez ici la page des <b>talents</b> (ctrl-a puis ctrl-c).
<br/>
<textarea name="talents" cols="62" rows="6" wrap="soft" style="width:100%;"><?php echo $_POST['talents']; ?>
</textarea>
<br />
<br />
Copiez-collez ici la page de <b>l'entrepot</b> (ctrl-a puis ctrl-c).
<br/>
<textarea name="matos" cols="62" rows="6" wrap="soft" style="width:100%;"><?php echo $_POST['matos']; ?>
</textarea>
<br /><br />
Insérez les <b>bonus de l'équipement</b> (<a href="javascript:show_hide('msg_equ');">cliquez ici pour plus d'infos</a>)
<div id="msg_equ" style="display:none;border:1px black solid; padding: 1em; margin-bottom:0.2em;">
Ce système n'est pas parfait, mais il permet néanmoins d'inclure la majorité des bonus liés aux équipements.<br />
<br />
Comment cela fonctionne-t'il?<br />
Vous devez faire le tour de vos équipements et ajouter à la main les bonus pour chaque catégorie.<br />
<br />
 * La colone <b>BF</b>, qui signifie <b>Bonus fixe</b>, va être utilisée pour les bonus du genre: <br />
<br />
embuscade	+2<br />
force de la nature	+4 +50% du niveau du héros<br />
<br />
Si vous avez plusieurs équipements qui vous donnent le même bonus, il suffit de les additionner (ou soustraire si l'un deux est négatif).<br />
<br />
 * La colonne <b>%</b>, qui est celle des <b>bonus en pourcentage</b>, est, elle, un peu particulière. Voici un exemple: <br />
<br />
explosion	-5%<br />
déclenchement	-5%<br />
<br />
Vous allez devoir sortir votre calculette, du moins, si vous avez plusieurs bonus % pour la même parade / attaque.<br />
Avec 1 seul, ça reste très simple.<br />
<br />
Comment calculer?<br />
- Si vous n'en avez qu'un -> Bonus/100 + 1  <br />
Ex: un bonus de +5% donnera 1.05 (5/100 + 1) tandis qu'un bonus de -5% donnera 0.95 (-5/100 + 1)<br />
<br />
- Si vous en avez plusieurs -> (Bonus1/100 + 1) * (Bonus2/100 + 1) * .. <br />
Ex: un bonus de 5% et un autre de 10% donnera 1.05 * 1.1 = 1.155 ((5/100 +1) * 10/100 + 1))<br />
Ex: un bonus de -10% et un autre de +5% donnera 0.9 * 1.05 = 0.945 ((-10/100 +1) * 5/100 + 1))<br />
<br />
L'idée est donc bien de multiplier les différents bonus en % et pas les additionner.<br />
<br />
A moins de cas rares, le résultat doit être compris entre 0.5 et 1.5<br />
<br />
Cela peut vous paraître un tantinet du chipottage, mais soyez rassurés, les bonus en % sont moins présents <br />
que les bonus fixes.<br />
</div>
<div style="border:1px black solid; padding: 1em;">
<table border=0>
<tr><th></th><th colspan=2 align='center'>Parades</th><th colspan=2 align='center'>Attaques</th><th></th></tr>
<tr><td></td><td align='center'>BF</td><td align='center'>%</td><td align='center'>BF</td><td align='center'>%</td>
	<td rowspan=<?php echo count($list) + 1;?> valign='top'>
		<a href="javascript:show_hide('msg_bonus_eq'); show_hide('bonus_eq');">c'est quoi ce truc?</a>
		<div id="msg_bonus_eq" style="display:none;border:1px black solid; padding: 0.3em; margin:0;max-width:250px;">
		Cela vous permet, une fois que vous avez placé tous vos bonus d'équipement, de générer une ligne (bouton "Récupérer bonus équipements") que vous conserverez qqpart.<br /><br />
		La fois suivante, vous pourrez la remettre ici et d'un click ré-intégrer toutes les valeurs de bonus dans les bonnes cases (bouton "Ré-insérer bonus équipements").
		<br /><br />
		Attention cependant de vous rappeller si vous n'avez pas changé d'équipement depuis la dernière fois.
		</div>
		<div id="bonus_eq">
		<textarea id="lst_eq" cols=22 rows=5 style="width:99%;"></textarea>
		<br />
		<input type="button" value="Récupérer bonus équipements" onclick="recup_bonus_eq();" style="width:99%;">
		<br />
		<input type="button" value="Ré-insérer bonus équipements" onclick="inser_bonus_eq();" style="width:99%;">
		</div>
	</td>
</tr>
<?php for ($i = 0; $i < count($list); $i++) { ?>
<tr>
	<td><?php echo $list_noms[$i]?></td>
	<td><input type='text' id="p<?php echo $list[$i]?>_bf" size=3 maxlength=3 name="p<?php echo $list[$i]?>_bf" value="<?php echo $_POST['p'.$list[$i].'_bf']; ?>"></td>
	<td><input type='text' id="p<?php echo $list[$i]?>_p" size=6 maxlength=6 name="p<?php echo $list[$i]?>_p" value="<?php echo $_POST['p'.$list[$i].'_p']; ?>"></td>
	<td><input type='text' id="a<?php echo $list[$i]?>_bf" size=3 maxlength=3 name="a<?php echo $list[$i]?>_bf" value="<?php echo $_POST['a'.$list[$i].'_bf']; ?>"></td>
	<td><input type='text' id="a<?php echo $list[$i]?>_p" size=6 maxlength=6 name="a<?php echo $list[$i]?>_p" value="<?php echo $_POST['a'.$list[$i].'_p']; ?>"</td>
</tr>
<?php } ?>
</table>

</div>
<br /><br />
<input type="submit" value="Envoyer" />
<br /><br />
</form>

<div style="width:99%;text-align:right;text-size:smaller;">Il a déjà été utilisé <b><?php echo $compteur?></b> fois depuis début 2012</div>
<div style="background-color:#efefef;border:1px solid #cccccc; padding:10px;margin-top:10px;">
<h2>Wodificator</h2>
Cet outil permet de rapidement formatter les caractéristiques d'un personnage de World of Dungeons : <a href="http://world-of-dungeons.fr">http://world-of-dungeons.fr</a>. 
C'est une initiative personnelle d'un joueur isolé sans rapport avec Neise Games GmbH.<br /><br />

<h3 style="margin:0;">Historique</h3>
<pre style="margin:0;">
v. 0.3b9 - 08/07/2012  - Correction coût PM Poings volant
v. 0.3b8 - 25/04/2012  - Correction pour le Tir à la fronde (merci Al Khawarizmi)
v. 0.3b7 - 20/04/2012  - Bug fix sous IE + rajout des consommables du sac mais en ne rerpenant qu'un exemplaire de chaque
v. 0.3b6 - 09/04/2012  - Ajout Poignard de terre + Correction info Don: Maître des racines
v. 0.3b5 - 09/04/2012  - Correction du talent Grand exorcisme qui n'apparaîssait pas
v. 0.3b4 - 02/04/2012  - Problème dans la section équipement lorsque le joueur n'avait pas de médailles
                       - (merci à Rhyp, Nelya et Azanyit'yia)
v. 0.3b3 - 31/03/2012  - Correction Tir à l'arc + ajout de "c'est quoi ce truc"
                       - Equipements séparés en 3 catégories et consommables plus repris
v. 0.3b2 - 29/03/2012  - Erreur Javascript lors de l'affichage des infos dans FF.
v. 0.3b1 - 28/03/2012  - Nettoyage complet du code, ajout de tous les talents manquants (classes et races) ainsi que des dons.
                       - Présentation des attaques / parades par catégorie.
                         (merci à Max, Nelya, Dragor Laciturne, Mirwenn, Tarshaid et tous les autres 
                         pour leur aide précieuse à m'inculquer le fonctionnement du schmillblick)
v. 0.2b4 - 10/03/2012  - Correction Poignarder qui n'avait pas les bonus d'attaque liés à la race
                       - Ajout des bonus d'attaques donnés par les dons
v. 0.2b3 - 09/03/2012  - Ajout des bonus de parades donnés par les dons
v. 0.2b2 - 08/03/2012  - Ajout du talent Fidèle de Rashon aux Résistances à la magie, déclenchement, fdn, embuscade & maladie
v. 0.2b1 - 07/03/2012  - Ajout du talent Zèle et du Don : explosif
                       - Ajout des bonus fixes aux talents d'init (Attaque rapide, Pied léger, Zèle, ..)
                       - Ajout du talent Veinard et retrait du talent Acrobatie à Éviter un coup et à Eviter un tir
                       - Ajout du talent Veinard à la Résistance à la magie, à la Résistance sociale, Inflexible, 
                         Confiance divine, Liberté du fou, Entêtement du bœuf, Calme stoïque et Discipline
                       - Correction dans le Don : utilisation alternative de poudre noire, des informations n'apparaissaient pas
                       - Ajout du talent Veinard à la Résistance décl. piège, à la Résistance Force de la nature, à la Résistance Explosion
                       - Ajout des talents Veinard, Appel de la hyène et Rire de la hyène à la Résistance Embuscade
                       - Ajout des talents Veinard et Coriacité de l'ours à la Résistance Maladie
                       - Ajout à la fiche de la Résistance à l'effroi 
                       - Ajout à la fiche de la Résistance tir de précision 
                       - Ajout à la fiche de la Résistance désorientation 
                       - Ajout à la fiche de la Résistance immobilisation 
                       - Ajout des informations pour les talents d'attributs
                       - Remplacement de tous les floor() par round() (arrondis sur les calculs)
v. 0.1b22 - 06/03/2012 - Correction Tir à l'arc, tir à l'arbalète, Combat à l'arme d'hast et Combat à l'arme de choc (le nom des talents a changé)
v. 0.1b21 - 10/01/2012 - obligation de mettre une classe et une race puisqu'elle ne peuvent plus être retrouvées auto. + ajout du compteur
v. 0.1b20 - 12/12/2011 - fix race/classe/titre, fix matos du à un changement de nomenclature (+2 -> (II))
                       - fix de corrections d'erreurs sur les talents ($skill -> $skills); fix ['soc'] -> ['social']
v. 0.1b19 - 21/09/2010 - re-re-fix sur les calculs Pts et Régén. mana...ajout du Don : Jet de glace mortel
v. 0.1b18 - 10/04/2010 - ajout de Sages conseils, Exposé scientifique, et Capitaine et Maréchal de camp
v. 0.1b17 - 28/03/2010 - re-fix sur les calculs Pts et Régén. mana...
v. 0.1b16 - 19/01/2010 - fix sur les calculs Pts de vie et de mana
v. 0.1b15 - 25/02/2009 - fix sur le calcul de resistance sociale, ajout d'Inflexible
                       - fix des calculs de modificateurs raciaux (merci asterix des ars)
v. 0.1b14 - 20/02/2009 - ajout de la gauge
                       - correction sur la prise en compte du matos
v. 0.1b13 - 12/02/2009 - correction de la reconnaissance du nouveau design
v. 0.1b12 - 12/02/2009 - correction de la reconnaissance du nom
                       - ajout des calculs pour les sorciers
                       - fix sur le calcul de tir transpercant
v. 0.1b11 - 07/02/2009 - ajout de formules pour la plupart de la bouffe (source: forum allemand, a verifier)
                       - ajout des effets de plusieurs talents (en cours)
                       - correction de la detection du nom du hero etdu monde
                       - correction du bug de parsing des comptes non-premium
                       - modification du look de rendu pour le nom et la signature
v. 0.1b10 - 04/02/2009 - ajout de l'affichage des effets de talents et de leur cout en mana
                       - ajout des effets de la bouffe, boisson et fumette (en cours)
v. 0.1b9  - 31/01/2009 - fix: liste de matos maintenant distingue la localisation quand elle est presente
v. 0.1b8  - 31/01/2009 - ajout de champs pour specifier le nom, race, class et monde a la main
                       - un peu de mise en forme des formulaires
v. 0.1b7  - 30/01/2009 - oops, j'avais oublie le combat a l'epee
v. 0.1b6  - 30/01/2009 - re-correction sur les defenses sociales
                       - ajout de l'affichage du matos
                       - ajout du calcul de presque tous les talents (manque encore un peu de risque-tout)
v. 0.1b5  - 29/01/2009 - correction des calculs de resistance sociale (merci Elminster)
                       - affichage optionnel des talents non appris
                       - ordre d'affichage des talents selon la valeur modifiee
v. 0.1b4  - 27/01/2009 - ajout du calcul des attaques, parades et effets, encore incomplet, en cours d'alimentation
v. 0.1b3  - 01/01/2009 - ajout du rendu des effets d'armure 
v. 0.1b2  - 31/12/2008 - ajout d'un numero de version, de l'historique
v. 0.1b1  - 30/12/2008 - premier essai et tests
</pre>

<br />
<h3 style="margin:0;">Credits</h3>
<div style="font-size:80%;">Ce bout de programme est une simple page en php (2500 lignes). Il a été principalement developpé par Zom (et complètment revu par Atchoum)
Depuis sa disparition de nos serveurs, quelques irréductibles tentent de pérenniser cet excellent outil.</div>
</div>
</div>
<?php } ?>

</body>
</html>
