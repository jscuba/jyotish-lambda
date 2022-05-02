<?php

require __DIR__.'/vendor/autoload.php';

use Jyotish\Base\Data;
use Jyotish\Base\Locality;
use Jyotish\Base\Analysis;
use Jyotish\Ganita\Method\Swetest;
use Jyotish\Dasha\Dasha;
use Jyotish\Panchanga\AngaDefiner;
use Jyotish\Graha\Lagna;
use Jyotish\Yoga\Yoga;
use Jyotish\Bala\AshtakaVarga;
use Jyotish\Bala\GrahaBala;
use Jyotish\Bala\RashiBala;
use Jyotish\Graha\Graha;

lambda(function ($event) {
	if (isset($event['path']) && $event['path'] == '/ping') {
		return strtotime('now');
	}

	$charttype = null;
	if (isset($event['charttype'])) {
		$charttype = $event['charttype'];
	}

	$longitude = null;
	if (isset($event['longitude'])) {
		$longitude = $event['longitude'];
	}

	$latitude = null;
	if (isset($event['latitude'])) {
		$latitude = $event['latitude'];
	}
	
	$altitude = null;
	if (isset($event['altitude'])) {
		$altitude = $event['altitude'];
	}
	
	$datetime = null;
	if (isset($event['datetime'])) {
		$datetime = $event['datetime'];
	}

	if (
		!isset($charttype) ||
		!isset($longitude) ||
		!isset($latitude) ||
		!isset($altitude) ||
		!isset($datetime)
	) {
		return '';
	}

	$locality = new Locality([
	            'longitude' => $longitude,
	            'latitude' => $latitude,
	            'altitude' => $altitude
	            ]);

	$now = new DateTime($datetime);
	$ganita = new Swetest(["swetest" => __DIR__.'/bin/']);
	$data = new Data($now, $locality, $ganita);

	$data->calcYoga([Yoga::TYPE_MAHAPURUSHA, Yoga::TYPE_DHANA, Yoga::TYPE_RAJA, Yoga::TYPE_NABHASHA, Yoga::TYPE_PARIVARTHANA, Yoga::TYPE_SANNYASA]);
	$data->calcVargaData([$charttype]);
	$analysis = new Analysis($data);

	$vargaData = $analysis->getVargaData($charttype);

	$ashtakaVarga = new AshtakaVarga($data);
	$vargaData['ashtakavarga'] = $ashtakaVarga->getBhinnAshtakavarga();

	$grahaBala = new GrahaBala($data);
	$vargaData['grahabala'] = $grahaBala->getBala();

	$rashiBala = new RashiBala($data);
	$vargaData['rashibala'] = $rashiBala->getBala();

	$angaDefiner = new AngaDefiner($data);

	$nakshatra = null;
	foreach ($vargaData['graha'] as $grahaKey => $value) {
		$nakshatra = $angaDefiner->getNakshatra(false, false, $grahaKey);
		$vargaData['graha'][$grahaKey]['nakshatra'] = $nakshatra;
		$Graha = Graha::getInstance($grahaKey)->setEnvironment($data);
		$vargaData['graha'][$grahaKey]['astangata'] = $Graha->isAstangata(); // combustion
		$vargaData['graha'][$grahaKey]['rashiAvastha'] = $Graha->getRashiAvastha(); // dignity
	}
	$nakshatra = $angaDefiner->getNakshatra(false, false, Lagna::KEY_LG);
	$vargaData['lagna'][Lagna::KEY_LG]['nakshatra'] = $nakshatra;

	// dasha
	$data = new Data($now, $locality, $ganita);
	$data->calcDasha(Dasha::TYPE_VIMSHOTTARI, null);
	$dasha = $data->getData();

	$vargaData['panchanga'] = $dasha['panchanga'];
	$vargaData['dasha'] = $dasha['dasha']['vimshottari'];

	return json_encode($vargaData);
});
