<?php
namespace Anexia\ComposerTools\Traits;

use Composer\Semver\VersionParser;

/**
 * Trait ComposerPackagistTrait
 * @package Anexia\Monitoring\Traits
 */
trait ComposerPackagistTrait
{
    /**
     * Get latest (stable) version number of composer laravel package (laravel/framework)
     *
     * @param string $packageName, the name of the package as registered on packagist, e.g. 'laravel/framework'
     * @return string
     */
    public function getLatestFrameworkVersion($packageName)
    {
        $lastVersion = '';

        // get version information from packagist
        $packagistUrl = 'https://packagist.org/packages/' . $packageName . '.json';

        try {
            $packagistInfo = json_decode(file_get_contents($packagistUrl));
            $versions = $packagistInfo->package->versions;
        } catch (\Exception $e) {
            $versions = [];
        }

        if (count($versions) > 0) {
            $latestStableNormVersNo = '';
            foreach ($versions as $versionData) {
                $versionNo = $versionData->version;
                $normVersNo = $versionData->version_normalized;
                $stability = VersionParser::normalizeStability(VersionParser::parseStability($versionNo));

                // only use stable version numbers
                if ($stability === 'stable' && version_compare($normVersNo, $latestStableNormVersNo) >= 0) {
                    $lastVersion = $versionNo;
                    $latestStableNormVersNo = $normVersNo;
                }
            }
        }

        return $lastVersion;
    }

    /**
     * Get information for composer installed packages (currently installed version and latest stable version)
     *
     * @return array
     */
    public function getComposerPackageData()
    {
        $moduleVersions = [];

        $installedJsonFile = getcwd() . '/../vendor/composer/installed.json';
        $packages = json_decode(file_get_contents($installedJsonFile));

        if (count($packages) > 0) {
            foreach ($packages as $package) {
                $name = $package->name;
                $latestStableVersNo = '';

                /**
                 * get latest stable version number
                 */
                // get version information from packagist
                $packagistUrl = 'https://packagist.org/packages/' . $name . '.json';

                try {
                    $packagistInfo = json_decode(file_get_contents($packagistUrl));
                    $versions = $packagistInfo->package->versions;
                } catch (\Exception $e) {
                    $versions = [];
                }

                if (count($versions) > 0) {
                    $latestStableNormVersNo = '';
                    foreach ($versions as $versionData) {
                        $versionNo = $versionData->version;
                        $normVersNo = $versionData->version_normalized;
                        $stability = VersionParser::normalizeStability(VersionParser::parseStability($versionNo));

                        // only use stable version numbers
                        if ($stability === 'stable' && version_compare($normVersNo, $latestStableNormVersNo) >= 0) {
                            $latestStableVersNo = $versionNo;
                            $latestStableNormVersNo = $normVersNo;
                        }
                    }
                }

                /**
                 * prepare result
                 */
                $moduleVersions[] = [
                    'name' => $name,
                    'installed_version' => $package->version,
                    'newest_version' => $latestStableVersNo
                ];
            }
        }

        return $moduleVersions;
    }
}