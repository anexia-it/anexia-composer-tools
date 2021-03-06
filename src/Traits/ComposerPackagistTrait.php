<?php

namespace Anexia\ComposerTools\Traits;

use Composer\Semver\VersionParser;

/**
 * Trait ComposerPackagistTrait
 *
 * @package Anexia\Monitoring\Traits
 */
trait ComposerPackagistTrait
{
    /**
     * Get latest (stable) version number of composer package
     *
     * @param   string  $packageName  The name of the package as registered on packagist, e.g. 'laravel/framework'
     *
     * @return string|null
     */
    public function getLatestPackageVersion($packageName)
    {
        $lastVersion = $this->getLatestPackage($packageName);

        if (is_object($lastVersion)) {
            return $lastVersion->version;
        }
    }

    /**
     * Return whichever object has the newer version
     *
     * @param   object  $versionData
     * @param   object  $lastVersion
     *
     * @return object
     */
    private function getNewerVersion($versionData, $lastVersion)
    {
        $versionNo     = $versionData->version;
        $normVersionNo = $versionData->version_normalized;
        $stability     = VersionParser::normalizeStability(VersionParser::parseStability($versionNo));
        $isStable      = $stability === 'stable';

        if ($lastVersion === null && $isStable) {
            return $versionData;
        }

        // only use stable version numbers
        if ($isStable && version_compare($normVersionNo, $lastVersion->version_normalized) >= 0) {
            return $versionData;
        }

        return $lastVersion;
    }

    /**
     * Get latest (stable) package from packagist
     *
     * @param   string  $packageName  The name of the package as registered on packagist, e.g. 'laravel/framework'
     *
     * @return object|null
     */
    public function getLatestPackage($packageName)
    {
        // get version information from packagist
        $packagistUrl  = 'https://packagist.org/packages/'.$packageName.'.json';
        $latestVersion = null;

        try {
            $packagistInfo = json_decode(file_get_contents($packagistUrl));
            $versions      = $packagistInfo->package->versions;
            foreach ($versions as $index => $version) {
                $latestVersion = $this->getNewerVersion($version, $latestVersion);
            }

            return $latestVersion;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get information for composer installed packages (currently installed version and latest stable version)
     *
     * @return array
     */
    public function getComposerPackageData()
    {
        $moduleVersions = [];

        $installedJsonFile = getcwd().'/../vendor/composer/installed.json';
        $installedJsonData = json_decode(file_get_contents($installedJsonFile));
        $packages          = $this->getPackagesFromInstalledData($installedJsonData);

        if (count($packages) > 0) {
            foreach ($packages as $package) {
                $name = $package->name;

                /**
                 * get latest stable version of the package
                 */
                $latestStable = $this->getLatestPackage($name);

                $module = [
                    'name'                       => $name,
                    'installed_version'          => $package->version,
                    'installed_version_licences' => $package->license,
                ];

                if ($latestStable !== null) {
                    $module['newest_version']          = $latestStable->version;
                    $module['newest_version_licences'] = $latestStable->license;
                }

                $moduleVersions[] = $module;
            }
        }

        return $moduleVersions;
    }

    /**
     * Get the package list from the 'installed' JSON structure. The format changed from Composer 1 to Composer 2.
     *
     * Format in Composer 1:
     * [
     *   {
     *     "name": "anexia/composer-tools",
     *     ...
     *   },
     *   ...
     * ]
     *
     * Format in Composer 2:
     * {
     *   "packages": [
     *     {
     *       "name": "anexia/composer-tools",
     *       ...
     *     },
     *     ...
     *   ],
     *   ...
     * }
     *
     * @param   object|array  $installedJsonData
     *
     * @return array
     */
    private function getPackagesFromInstalledData($installedJsonData)
    {
        $packages = [];

        // Composer 1
        if (is_array($installedJsonData)) {
            $packages = $installedJsonData;
        }

        // Composer 2
        if (is_object($installedJsonData) && property_exists($installedJsonData, 'packages')) {
            $packages = $installedJsonData->packages;
        }

        return $packages;
    }
}
