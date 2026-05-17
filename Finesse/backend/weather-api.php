<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
$city = trim($_GET['city'] ?? 'London');
$lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
$lon = isset($_GET['lon']) ? (float) $_GET['lon'] : null;

function condition_from_code(int $code): string {
  if ($code === 0) return 'mild';
  if (in_array($code, [51,53,55,56,57,61,63,65,66,67,80,81,82,95,96,99], true)) return 'rain';
  if (in_array($code, [71,73,75,77,85,86], true)) return 'cold';
  return 'mild';
}

if (WEATHER_API_KEY) {
  if ($lat !== null && $lon !== null) {
    $url = 'https://api.openweathermap.org/data/2.5/weather?lat=' . urlencode((string)$lat) . '&lon=' . urlencode((string)$lon) . '&units=metric&appid=' . WEATHER_API_KEY;
  } else {
    $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($city) . '&units=metric&appid=' . WEATHER_API_KEY;
  }
  $r = @file_get_contents($url);
  if ($r) { echo $r; exit; }
}

// Real weather fallback without API key (Open-Meteo)
try {
  if ($lat === null || $lon === null) {
    $geo = @file_get_contents('https://geocoding-api.open-meteo.com/v1/search?name=' . urlencode($city) . '&count=1&language=en&format=json');
    if ($geo) {
      $geoData = json_decode($geo, true);
      if (!empty($geoData['results'][0])) {
        $lat = (float)$geoData['results'][0]['latitude'];
        $lon = (float)$geoData['results'][0]['longitude'];
        if (!empty($geoData['results'][0]['name'])) $city = $geoData['results'][0]['name'];
      }
    }
  } else {
    $rev = @file_get_contents('https://geocoding-api.open-meteo.com/v1/reverse?latitude=' . urlencode((string)$lat) . '&longitude=' . urlencode((string)$lon) . '&count=1&language=en&format=json');
    if ($rev) {
      $revData = json_decode($rev, true);
      if (!empty($revData['results'][0]['name'])) $city = $revData['results'][0]['name'];
    }
  }

  if ($lat !== null && $lon !== null) {
    $meteo = @file_get_contents('https://api.open-meteo.com/v1/forecast?latitude=' . urlencode((string)$lat) . '&longitude=' . urlencode((string)$lon) . '&current_weather=true&timezone=auto');
    if ($meteo) {
      $m = json_decode($meteo, true);
      if (!empty($m['current_weather'])) {
        $temp = (float)($m['current_weather']['temperature'] ?? 22);
        $code = (int)($m['current_weather']['weathercode'] ?? 0);
        $condition = condition_from_code($code);
        echo json_encode([
          'name' => $city ?: 'Your location',
          'main' => ['temp' => $temp],
          'weather' => [[
            'main' => ucfirst($condition),
            'description' => $condition . ' weather'
          ]],
          'condition' => $condition,
          'source' => 'open-meteo'
        ]);
        exit;
      }
    }
  }
} catch (Throwable $e) {
  // ignore and use mock fallback
}

// Fallback mock
$conditions = ['hot','cold','rain','mild'];
$c = $conditions[array_rand($conditions)];
$temp = ['hot'=>30,'cold'=>6,'rain'=>15,'mild'=>22][$c];
$mockName = $city !== '' ? $city : (($lat !== null && $lon !== null) ? 'Your location' : 'London');
echo json_encode(['name'=>$mockName,'main'=>['temp'=>$temp],'weather'=>[['main'=>ucfirst($c),'description'=>$c.' weather']],'mock'=>true,'condition'=>$c]);
