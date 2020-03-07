<?php

require_once __DIR__ . '/vendor/autoload.php';

$participantsPerGroup = $argv[1] ?? 7;
$dataOrigin = $argv[2] ?? 'rnd';

echo '=========================================================='.PHP_EOL;
echo 'participants per groups: '.$participantsPerGroup.PHP_EOL;
echo 'data origin: '.$dataOrigin.PHP_EOL;
echo '=========================================================='.PHP_EOL;

$participants = [];

/* randomize hackers */
if ($dataOrigin == 'rnd') {
    for ($i=1; $i < 82; $i++) { 
        $name = 'Participant '.$i;
        $skill = rand(1,3);
        $gender = rand(0,1);
        $genders = [
            0 => 'M',
            1 => 'F',
        ];
        $age = rand(16,42);
        $participants[] = [
            'number' => $i,
            'name' => $name,
            'skill' => $skill,
            'gender' => $genders[$gender],
            'age' => $age,
        ];
    }
}

/* load from csv */
if ($dataOrigin == 'csv') {
    $row = 1;
    if (($handle = fopen("hacka.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $num = count($data);
            if ($row != 1 && $data[5] == 's') {
                $participants[] = [
                    'number' => $data[0],
                    'name' => $data[1],
                    'skill' => $data[2],
                    'gender' => $data[4],
                    'age' => $data[3],
                ];
            }
            $row++;
        }
        fclose($handle);
    }
}
echo '=========================================================='.PHP_EOL;
echo 'participants list: '.PHP_EOL;
print_r($participants);
echo '=========================================================='.PHP_EOL;


$initialNumberOfGroups = floor(count($participants) / $participantsPerGroup);
$module = count($participants) % $participantsPerGroup;

$groupsMin = 0;
$groupsToReduce = 0;
if ($module > 0) {
    $groupsToReduce = $participantsPerGroup - $module;
    $groupsMin = $groupsToReduce + 1;
}

$groupsMax = $initialNumberOfGroups - $groupsToReduce;

$groupsTotal = $groupsMax + $groupsMin;

echo 'number of participants: '.count($participants).PHP_EOL;
echo 'participants per group: '.$participantsPerGroup.PHP_EOL;
echo 'initial number of groups: '.$initialNumberOfGroups.PHP_EOL;
echo 'left participants: '.$module.PHP_EOL;
echo '=========================================================='.PHP_EOL;
echo 'groups to reduce: '.$groupsToReduce.PHP_EOL;
echo '=========================================================='.PHP_EOL;
echo 'groups of 7 participants: '.$groupsMax.PHP_EOL;
echo 'groups of 6 participants: '.$groupsMin.PHP_EOL;
echo '=========================================================='.PHP_EOL;
echo 'total of groups: '.$groupsTotal.PHP_EOL;
echo '=========================================================='.PHP_EOL;

$colName = [];
$colSkill = [];
$colGender = [];
$colAge = [];
foreach ($participants as $key => $row) {
    $colName[$key]  = $row['name'];
    $colSkill[$key]  = $row['skill'];
    $colGender[$key] = $row['gender'];
    $colAge[$key] = $row['age'];
}

array_multisort($colSkill, SORT_DESC, $colAge, SORT_DESC, $colGender, SORT_ASC, $participants);

echo '=========================================================='.PHP_EOL;
echo 'participants reordered: '.PHP_EOL;
print_r($participants);
echo '=========================================================='.PHP_EOL;

$groups = [];
$groupsData = [];
for ($i=0; $i <=$groupsTotal ; $i++) { 
    $groups[$i] = [];
    $groupsData[$i] = [
        'group' => $i,
        'participants' => 0,
        'skills' => 0,
        'ages' => 0,
        'males' => 0,
        'females' => 0,
    ];
}

while (count($participants) > 0) {
    foreach ($groups as $key => $value) {
        if (count($groups[$key]) < 7 && isset($participants[0])){
            $groups[$key][] = $participants[0];
            $groupsData[$key]['participants']++;
            $groupsData[$key]['skills'] = $groupsData[$key]['skills']+$participants[0]['skill'];
            $groupsData[$key]['ages'] = $groupsData[$key]['ages'] + $participants[0]['age'];
            if ($participants[0]['gender'] == 'M') {
                $groupsData[$key]['males']++;
            } else {
                $groupsData[$key]['females']++;
            }
            array_shift($participants);
        }
    }
}

echo '=========================================================='.PHP_EOL;
echo 'initial distribuition: '.PHP_EOL;
print_r($groups);
echo '=========================================================='.PHP_EOL;

echo 'initial group data: '.PHP_EOL;
print_r($groupsData);
echo '=========================================================='.PHP_EOL;

$enough = false;
$lap = 0;

while (!$enough) {
    $stronger = null;
    $weaker = null;
    $maxSkill = 0;
    $minSkill = 1000;
    foreach($groupsData as $data) {
        if($data['skills'] > $maxSkill) {
            $maxSkill = $data['skills'];
            $stronger = $data['group'];
        }
        if($data['skills'] < $minSkill) {
            $minSkill = $data['skills'];
            $weaker = $data['group'];
        }
    }
    
    $diff = $maxSkill - $minSkill;
    
    if ($diff > 0) {
        $participantWeaker = null;
        $participantStronger = $groups[$stronger][0];
        if ($diff > 2) {
            $participantWeaker = end($groups[$weaker]);
            $weakerKey = (!empty($groups[$weaker])) ? array_keys($groups[$weaker])[count($groups[$weaker])-1] : null;
        } else if ($diff == 2) {
            foreach ($groups[$weaker] as $partWeaker => $partWeakerData) {
                $currDiff = $groups[$stronger][0]['skill'] - $partWeakerData['skill'];
                if ($currDiff == 1){
                    $participantWeaker = $groups[$weaker][$partWeaker];
                    $weakerKey = $partWeaker;
                }
            }
        }

        if (empty($participantWeaker) || $diff == 1) {
            $enough = true;
        } else {
            echo 'TRANSFER'.PHP_EOL;
            echo '=========================================================='.PHP_EOL;
            echo 'stronger group: '.$stronger.' with '.$maxSkill.PHP_EOL;
            echo 'weaker group: '.$weaker.' with '.$minSkill.PHP_EOL;
            echo 'diff: '.$diff.PHP_EOL;
            echo '=========================================================='.PHP_EOL;

            if ($participantStronger['skill'] - $participantWeaker['skill'] <= $diff) {
                array_shift($groups[$stronger]);
                $groupsData[$stronger]['skills'] = $groupsData[$stronger]['skills'] - $participantStronger['skill'];
                $groupsData[$stronger]['ages'] = $groupsData[$stronger]['ages'] - $participantStronger['age'];
                unset($groups[$weaker][$weakerKey]);
                $groupsData[$weaker]['skills'] = $groupsData[$weaker]['skills'] - $participantWeaker['skill'];
                $groupsData[$weaker]['ages'] = $groupsData[$weaker]['ages'] - $participantWeaker['age'];
        
                array_unshift($groups[$weaker], $participantStronger);
                $groupsData[$weaker]['skills'] = $groupsData[$weaker]['skills'] + $participantStronger['skill'];
                $groupsData[$weaker]['ages'] = $groupsData[$weaker]['skills'] + $participantStronger['age'];
                array_push($groups[$stronger], $participantWeaker);
                $groupsData[$stronger]['skills'] = $groupsData[$stronger]['skills'] + $participantWeaker['skill'];
                $groupsData[$stronger]['ages'] = $groupsData[$stronger]['ages'] + $participantWeaker['age'];
            }
            print_r($participantStronger);
            print_r($participantWeaker);
    
            $colName = [];
            $colSkill = [];
            $colGender = [];
            $colAge = [];
            foreach ($groups[$weaker] as $key => $row) {
                $colName[$key]  = $row['name'];
                $colSkill[$key]  = $row['skill'];
                $colGender[$key] = $row['gender'];
                $colAge[$key] = $row['age'];
            }
    
            array_multisort($colSkill, SORT_DESC, $colAge, SORT_DESC, $colGender, SORT_ASC, $groups[$weaker]);
    
            $colName = [];
            $colSkill = [];
            $colGender = [];
            $colAge = [];
            foreach ($groups[$stronger] as $key => $row) {
                $colName[$key]  = $row['name'];
                $colSkill[$key]  = $row['skill'];
                $colGender[$key] = $row['gender'];
                $colAge[$key] = $row['age'];
            }
    
            array_multisort($colSkill, SORT_DESC, $colAge, SORT_DESC, $colGender, SORT_ASC, $groups[$stronger]);
        }
    } else {
        $enough = true;
    }
    $lap++;
    if ($lap >= $groupsTotal) {
        $enough = true;
    }
}

echo 'FINAL TEAM DATA'.PHP_EOL;
echo '=========================================================='.PHP_EOL;
print_r($groupsData);
echo '=========================================================='.PHP_EOL;

echo 'FINAL TEAMS'.PHP_EOL;
echo '=========================================================='.PHP_EOL;
print_r($groups);
echo '=========================================================='.PHP_EOL;

echo PHP_EOL;
echo 'TEAMS'.PHP_EOL;
echo '=========================================================='.PHP_EOL;
foreach ($groups as $key => $part) {
    echo 'GROUP '.($key + 1).PHP_EOL;
    foreach($part as $dataPart) {
        echo $dataPart['number'].' - '.$dataPart['name'].PHP_EOL;
    }
    echo '=========================================================='.PHP_EOL;
}

echo PHP_EOL;
echo 'TEAMS BY NUMBER'.PHP_EOL;
echo '=========================================================='.PHP_EOL;
foreach ($groups as $key => $part) {
    echo 'GROUP '.($key + 1).PHP_EOL;
    foreach($part as $dataPart) {
        echo $dataPart['number'].' ';
    }
    echo PHP_EOL;
    echo '=========================================================='.PHP_EOL;
}