<?php
class ConsistenceHash
{
    const VIRTUAL_NUMBER = 100;

    private $serverList = [];

    protected function hash($str) {
        // hash(i) = hash(i-1) * 33 + str[i]
        $hash = 0;
        $s    = md5($str);
        $seed = 5;
        $len  = 32;
        for ($i = 0; $i < $len; $i++) {
            // (hash << 5) + hash 相当于 hash * 33
            //$hash = sprintf("%u", $hash * 33) + ord($s{$i});
            //$hash = ($hash * 33 + ord($s{$i})) & 0x7FFFFFFF;
            $hash = ($hash << $seed) + $hash + ord($s{$i});
        }

        return $hash & 0x7FFFFFFF;
    }

    public function addServer($host)
    {
        for ($i = 0; $i < self::VIRTUAL_NUMBER; $i++) {
            $this->addVirtualHost($host."#".$i);
        }
    }

    protected function addVirtualHost($hostString)
    {
        $key = $this->hash($hostString);
        if (isset($this->serverList[$key])) {
            die("{$key}({$hostString}):is exists.");
        }
        $this->serverList[$key] = $hostString;
    }

    public function removeHost($host)
    {
        for ($i = 0; $i < self::VIRTUAL_NUMBER; $i++) {
            $this->addVirtualHost($host."#".$i);
        }
    }

    protected function removeVirtualHost($hostString)
    {
        $key = $this->hash($hostString);
        if (!isset($this->serverList[$key])) {
            die("{$key}({$hostString}):is not exists.");
        }
        unset($this->serverList[$key]);
    }

    public function sort()
    {
        ksort($this->serverList);
    }

    public function getHost($str)
    {
        $key = $this->hash($str);
        $keyArray = array_keys($this->serverList);
        $len = sizeof($keyArray);

        if (($key <= $keyArray[0]) || ($key >= $keyArray[$len - 1])) {
            return $this->serverList[$keyArray[0]];
        }

        foreach ($keyArray as $serverKey) {
            if ($key <= $serverKey) {
                return $this->serverList[$serverKey];
            }
        }

        return $this->serverList[$keyArray[$len - 1]];
    }
}

echo "10 server:\n";
$serverArray = [];
$ch = new ConsistenceHash();
for ($i = 0; $i < 10; $i++) {
    $host = '192.168.1.' . (100 + $i);
    $serverArray[] = $host;
    $ch->addServer($host);
}

$ch->sort();

$hostSum = [];
$result = [];
for ($i =0; $i < 5000; $i ++) {
    $host = $ch->getHost($i);
    $result[$i] = $host;
    $host = substr($host, 0, 13);
    if (isset($hostSum[$host])) {
        $hostSum[$host] += 1;
    } else {
        $hostSum[$host] = 1;
    }
}

arsort($result);
$filename = __DIR__ . '/hash.txt';
$fp = fopen($filename, "wb+");
foreach ($result as $key => $host) {
    fwrite($fp, sprintf("%4d:%s\n", $key, $host));
}
fclose($fp);

foreach ($hostSum as $host => $times) {
    echo sprintf("%s times: %d\n", $host, $times);
}

$server10 = $result;

echo "9 server:\n";

/////////////////9 server/////////////////////
$serverArray = [];
$ch = new ConsistenceHash();
for ($i = 0; $i < 9; $i++) {
    $host = '192.168.1.' . (100 + $i);
    $serverArray[] = $host;
    $ch->addServer($host);
}

$ch->sort();

$hostSum = [];
$result = [];
for ($i =0; $i < 5000; $i ++) {
    $host = $ch->getHost($i);
    $result[$i] = $host;
    $host = substr($host, 0, 13);
    if (isset($hostSum[$host])) {
        $hostSum[$host] += 1;
    } else {
        $hostSum[$host] = 1;
    }
}

arsort($result);
$filename = __DIR__ . '/hash_9.txt';
$fp = fopen($filename, "wb+");
foreach ($result as $key => $host) {
    fwrite($fp, sprintf("%4d:%s\n", $key, $host));
}
fclose($fp);

foreach ($hostSum as $host => $times) {
    echo sprintf("%s times: %d\n", $host, $times);
}

$server9 = $result;

echo "is not at the same server:\n";
for ($i =0; $i < 5000; $i ++) {
    $host10 = substr($server10[$i], 0, 13);
    $host9 = substr($server9[$i], 0, 13);
    if ($host10 != $host9) {
        echo sprintf("i:%4d server10:%s server9:%s\n", $i, $host10, $host9);
    }
}
echo "success.\n";
