<?php 
declare(strict_types=1);

namespace Lepidosteus\Geoip;

use InvalidArgumentException;

class Config
{
    public function __construct(
        protected string $db_path = __DIR__.'/../data/geoip.mmdb',
        protected ?string $licence_key = null,
        protected bool $exception_when_not_found = false,
        protected string $tmp_prefix = 'Lepidosteus_Geoip',
        protected string $lookup_binary = 'mmdblookup',
        protected string $unspecified_country_code = 'ZZ', // ZZ is the actual CLDR code for undefined / unkown country
    ) { 
        if (\pathinfo($db_path, \PATHINFO_EXTENSION) !== 'mmdb') {
            throw new InvalidArgumentException('Database path must end in .mmdb');
        }
    }

    public function exception_when_not_found(): bool
    {
        return $this->exception_when_not_found;
    }

    public function unspecified_country_code(): string
    {
        return $this->unspecified_country_code;
    }

    public function db_path(): string
    {
        return $this->db_path;
    }

    public function tmp_prefix(): string
    {
        return $this->tmp_prefix;
    }

    public function lookup_binary(): string
    {
        return $this->lookup_binary;
    }

    public function url(): string
    {
        if (!$this->licence_key) {
            throw new InvalidArgumentException('Cannot generate database url without license key');
        }

        return 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key='.$this->licence_key.'&suffix=tar.gz';
    }
}
