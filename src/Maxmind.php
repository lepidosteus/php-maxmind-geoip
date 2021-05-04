<?php 
declare(strict_types=1);

namespace Lepidosteus\Geoip;

class Maxmind
{
    const LOOKUP_COUNTRY = 'country';
    const LOOKUP_REGISTERED_COUNTRY = 'registered_country';
    const LOOKUP_CONTINENT = 'continent';

    public function __construct(
        protected ?Config $config = null
    ) 
    { 
        if (!$this->config) {
            $this->config = new Config();
        }
    }

    public function country(string $ip)
    {
        return $this->lookup($ip, self::LOOKUP_COUNTRY);
    }

    public function registered_country(string $ip)
    {
        return $this->lookup($ip, self::LOOKUP_REGISTERED_COUNTRY);
    }

    public function continent(string $ip)
    {
        return $this->lookup($ip, self::LOOKUP_CONTINENT);
    }

    protected function lookup(string $ip, string $match): string
    {
        if (!\in_array($match, [self::LOOKUP_COUNTRY, self::LOOKUP_REGISTERED_COUNTRY, self::LOOKUP_CONTINENT])) {
            throw new \InvalidArgumentException('Unknown lookup requested');
        }

        $executableFinder = new \Symfony\Component\Process\ExecutableFinder();
        $binary = $executableFinder->find($this->config->lookup_binary());
        if (!$binary) {
            throw new \RuntimeException('Could not find the lookup binary');
        }
        $process = new \Symfony\Component\Process\Process([
            $binary, 
            '--file',
            $this->config->db_path(), 
            '--ip',
            $ip, 
            $match,
            $match == self::LOOKUP_CONTINENT ? 'code' : 'iso_code',
        ]);
        $process->run();
        if (!$process->isSuccessful()) {
            if ($this->config->exception_when_not_found()) {
                throw new \RuntimeException('Lookup process failed to run properly');
            }
            return $this->config->unspecified_country_code();
        }
        if (!preg_match('/"([A-Z]{2})" <utf8_string>/', $process->getOutput(), $m)) {
            if ($this->config->exception_when_not_found()) {
                throw new \RuntimeException('Lookup process returned unknown format');
            }
            return $this->config->unspecified_country_code();
        }
        return $m[1];
    }

    public function updateDatabase(bool $overwrite = false): bool
    {
        $db_path = $this->config->db_path();
        
        if (\file_exists($db_path) && !$overwrite) {
            return false;
        }
        
        $url = $this->config->url();
        if (!$url) {
            return false;
        }

        $tmp_path = sys_get_temp_dir() .'/'. uniqid($this->config->tmp_prefix(), true) . '.tar.gz';

        if (file_exists($tmp_path)) {
            return false;
        }        

        try {
            $curl = curl_init();
            if (!$curl) {
                return false;
            }
            $fh = fopen($tmp_path, "w");
            if (!$fh) {
                return false;
            }
            curl_setopt_array($curl, array(
                CURLOPT_FILE    => $fh,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_URL     => $url,
            ));
            if (!curl_exec($curl)) {
                return false;
            }
        } finally {
            if ($fh) {
                fclose($fh);
            }
            if ($curl) {
                curl_close($curl);
            }
        }

        try {
            $p = new \PharData($tmp_path);
            foreach ($p as $pf) {
                $db_internal_folder = $pf->getFileName();
            }
            /** @var \PharFileInfo */
            $file = $p->offsetGet($db_internal_folder.'/GeoLite2-Country.mmdb');
            if (!$file) {
                return false;
            }
            if (!\file_put_contents($db_path, $file->getContent())) {
                return false;
            }
        } finally {
            \unlink($tmp_path);
        }

        return true;
    }
}
