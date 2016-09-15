<?php

/**
 * Checks for vulnerabilities at wpvulndb.com
 *
 * @author markri http://github.com/markri
 * @license MIT
 */

require( __DIR__ . '/vendor/autoload.php' );


use GuzzleHttp\Exception\ClientException;


if ( ! class_exists( 'WP_CLI' ) ) {
    // Whoops, something is wrong
	return;
}

if ( ! class_exists( 'WpSecCheck' ) ) {

    class WpSecCheck
    {
        private $outputType = true;

        private $coreVulnerabilityCount = 0;
        private $pluginVulnerabilityCount = 0;
        private $themeVulnerabilityCount = 0;

        const OUTPUT_USER = 'user';
        const OUTPUT_JSON = 'json';
        const OUTPUT_NAGIOS = 'nagios';

        /**
         * @param $ags
         * @param $assoc_args
         */
        public function __invoke($ags, $assoc_args)
        {
            // Parse user options
            $checkCore = true;
            $checkThemes = true;
            $checkPlugins = true;

            switch ($assoc_args['type']) {
                case 'core':
                    $checkThemes = false;
                    $checkPlugins = false;
                    break;
                case 'themes':
                    $checkCore = false;
                    $checkPlugins = false;
                    break;
                case 'plugins':
                    $checkCore = false;
                    $checkThemes = false;
                    break;
                default:
                    break;
            }

            $this->outputType = $assoc_args['output'];


            // Validate wordpress installation
            $output = WP_CLI::launch_self('core is-installed', array(), array(), false, true);
            if ($output->return_code == 1) {
                WP_CLI::error('No wordpress installation found');
            }

            // Execute
            if ($checkCore) {
                $this->checkCoreVulnerability();
            }

            if ($checkPlugins) {
                $this->checkPluginVulnerabilities();
            }

            if ($checkThemes) {
                $this->checkThemeVulnerabilities();
            }

            // Output
            switch ($this->outputType) {
                case self::OUTPUT_USER:
                    if ($this->coreVulnerabilityCount == 0 && $this->pluginVulnerabilityCount == 0 && $this->themeVulnerabilityCount == 0) {
                        WP_CLI::line('-----------------------------------------------');
                        WP_CLI::line('');
                        WP_CLI::success('No vulnerabilities found');
                    } else {
                        WP_CLI::line('-----------------------------------------------');
                        WP_CLI::line('');
                        WP_CLI::error(
                            sprintf(
                                '%s core, %s plugin and %s theme vulnerabilities found',
                                $this->coreVulnerabilityCount,
                                $this->pluginVulnerabilityCount,
                                $this->themeVulnerabilityCount
                            )
                        );
                    }

                    break;
                case self::OUTPUT_NAGIOS:
                    if ($this->coreVulnerabilityCount == 0 && $this->pluginVulnerabilityCount == 0 && $this->themeVulnerabilityCount == 0) {
                        WP_CLI::line('No vulnerabilities found');
                        exit(0);
                    } else {
                        WP_CLI::line(
                            sprintf(
                                '%s core, %s plugin and %s theme vulnerabilities found',
                                $this->coreVulnerabilityCount,
                                $this->pluginVulnerabilityCount,
                                $this->themeVulnerabilityCount
                            )
                        );
                        exit(2);
                    }
                    break;
                case self::OUTPUT_JSON:

                    $output = [
                        'core' => $this->coreVulnerabilityCount,
                        'plugins' => $this->pluginVulnerabilityCount,
                        'themes' => $this->themeVulnerabilityCount
                    ];

                    WP_CLI::line(json_encode($output));

                    break;
            }
        }


        /**
         * Checks core version at wpvulndb
         */
        private function checkCoreVulnerability()
        {
            // Get version throug internal WP_CLI command
            $output = WP_CLI::launch_self('core version', array(), array(), false, true);
            $coreVersion = trim($output->stdout);

            switch ($this->outputType) {
                case self::OUTPUT_JSON:
                    break;
                case self::OUTPUT_NAGIOS:
                    break;
                default:
                    WP_CLI::line('');
                    WP_CLI::line('-----------------------------------------------');
                    WP_CLI::line(sprintf('Checking core vulnerabilities for version %s', $coreVersion));
                    WP_CLI::line('-----------------------------------------------');
                    WP_CLI::line('');
                    break;
            }

            // Connect to wpvulndb
            $parameter = intval(str_replace('.', '', $coreVersion));

            $client = new GuzzleHttp\Client();
            $url = sprintf('https://wpvulndb.com/api/v2/wordpresses/%s', $parameter);

            try {
                $res = $client->request('GET', $url);
            } catch (ClientException $e) {
                WP_CLI::error(sprintf('Couldn\'t check wpvulndb @ %s', $url));
            }

            $json = json_decode($res->getBody(), true);

            if (!array_key_exists($coreVersion, $json)) {
                WP_CLI::error(sprintf('Version %s not found on wpvulndb', $coreVersion));
            }


            // Process found vulnerabilities
            $vulnerabilities = $json[$coreVersion]['vulnerabilities'];
            $this->coreVulnerabilityCount = count($vulnerabilities);

            if (empty($vulnerabilities)) {

                switch ($this->outputType) {
                    case self::OUTPUT_JSON:
                        break;
                    case self::OUTPUT_NAGIOS:
                        break;
                    default:
                        WP_CLI::line(sprintf('No known core vulnerabilities found in version %s', $coreVersion));
                        WP_CLI::line('');
                        break;
                }

                return;
            }

            switch ($this->outputType) {
                case self::OUTPUT_JSON:
                    break;
                case self::OUTPUT_NAGIOS:
                    break;
                default:
                    WP_CLI::line('');
                    WP_CLI::line(sprintf('Found %s core vulnerabilities:', count($vulnerabilities)));
                    break;
            }

            foreach ($vulnerabilities as $vulnerability) {

                switch ($this->outputType) {
                    case self::OUTPUT_JSON:
                        break;
                    case self::OUTPUT_NAGIOS:
                        break;
                    default:
                        WP_CLI::line('-----------------------------');
                        WP_CLI::line(sprintf('Title: %s', $vulnerability['title']));

                        if (array_key_exists('cve', $vulnerability['references'])) {
                            $cves = $vulnerability['references']['cve'];
                            WP_CLI::line(sprintf('CVE\'s: %s', implode(', ', $cves)));
                        }

                        WP_CLI::line(
                            sprintf(
                                'Fixed in: %s',
                                array_key_exists('fixed_in', $vulnerability) ? $vulnerability['fixed_in'] : 'fix n/a'
                            )
                        );
                        break;
                }
            }

            return true;
        }


        /**
         * Check plugins at wpvulndb
         */
        private function checkPluginVulnerabilities()
        {
            $this->pluginVulnerabilityCount = 0;
            $output = WP_CLI::launch_self('plugin list', array(), array('format' => 'json'), false, true);
            $plugins = json_decode($output->stdout, true);
            $client = new GuzzleHttp\Client();

            switch ($this->outputType) {
                case self::OUTPUT_JSON:
                    break;
                case self::OUTPUT_NAGIOS:
                    break;
                default:
                    WP_CLI::line('');
                    WP_CLI::line('-------------------------------');
                    WP_CLI::line('Checking plugin vulnerabilities');
                    WP_CLI::line('-------------------------------');
                    WP_CLI::line('');
                    WP_CLI::line('Vulnerabilities:');
                    break;
            }

            foreach ($plugins as $plugin) {

                $title = $plugin['name'];
                $version = $plugin['version'];

                $url = sprintf('https://wpvulndb.com/api/v2/plugins/%s', $title);

                try {
                    $res = $client->request('GET', $url);
                } catch (ClientException $e) {
                    continue;
                }

                $json = json_decode($res->getBody(), true);

                if (!array_key_exists($title, $json)) {
                    WP_CLI::error(sprintf('Unexepcted response from wpvulndb for plugin %s', $title));
                }

                $vulnerabilities = $json[$title]['vulnerabilities'];

                foreach ($vulnerabilities as $vulnerability) {
                    $safeVersion = $vulnerability['fixed_in'];

                    if (!$this->isVersionLessThan($version, $safeVersion)) {
                        continue;
                    }

                    $this->pluginVulnerabilityCount++;

                    switch ($this->outputType) {
                        case self::OUTPUT_JSON:
                            break;
                        case self::OUTPUT_NAGIOS:
                            break;
                        default:
                            WP_CLI::line('-----------------------------');
                            WP_CLI::line(sprintf('Plugin: %s', $title));
                            WP_CLI::line(sprintf('Version: %s', $version));
                            WP_CLI::line('Vulnerable: yes');

                            if (array_key_exists('cve', $vulnerability['references'])) {
                                $cves = $vulnerability['references']['cve'];
                                WP_CLI::line(sprintf('CVE\'s: %s', implode(', ', $cves)));
                            }

                            WP_CLI::line(
                                sprintf(
                                    'Fixed in: %s',
                                    empty($vulnerability['fixed_in']) ? 'fix n/a' : $vulnerability['fixed_in']
                                )
                            );

                            break;
                    }
                }
            }


            if ($this->pluginVulnerabilityCount === 0) {
                switch ($this->outputType) {
                    case self::OUTPUT_JSON:
                        break;
                    case self::OUTPUT_NAGIOS:
                        break;
                    default:
                        WP_CLI::line('No known plugin vulnerabilities found');
                        break;
                }
            }
        }


        private function checkThemeVulnerabilities()
        {
            $this->themeVulnerabilityCount = 0;
            $output = WP_CLI::launch_self('theme list', array(), array('format' => 'json'), false, true);
            $themes = json_decode($output->stdout, true);

            $client = new GuzzleHttp\Client();

            switch ($this->outputType) {
                case self::OUTPUT_JSON:
                    break;
                case self::OUTPUT_NAGIOS:
                    break;
                default:
                    WP_CLI::line('');
                    WP_CLI::line('------------------------------');
                    WP_CLI::line('Checking theme vulnerabilities');
                    WP_CLI::line('------------------------------');
                    WP_CLI::line('');
                    WP_CLI::line('Vulnerabilities:');
                    break;
            }

            foreach ($themes as $theme) {

                $title = $theme['name'];
                $version = $theme['version'];

                $url = sprintf('https://wpvulndb.com/api/v2/themes/%s', $title);

                try {
                    $res = $client->request('GET', $url);
                } catch (ClientException $e) {
                    continue;
                }

                $json = json_decode($res->getBody(), true);

                if (!array_key_exists($title, $json)) {
                    WP_CLI::error(sprintf('Unexpected response from wpvulndb for theme %s', $title));
                }

                $vulnerabilities = $json[$title]['vulnerabilities'];

                foreach ($vulnerabilities as $vulnerability) {
                    $safeVersion = $vulnerability['fixed_in'];

                    if (!$this->isVersionLessThan($version, $safeVersion)) {
                        continue;
                    }

                    $this->themeVulnerabilityCount++;

                    switch ($this->outputType) {
                        case self::OUTPUT_JSON:
                            break;
                        case self::OUTPUT_NAGIOS:
                            break;
                        default:
                            WP_CLI::line('-----------------------------');
                            WP_CLI::line(sprintf('Theme: %s', $title));
                            WP_CLI::line(sprintf('Version: %s', $version));
                            WP_CLI::line('Vulnerable: yes');
                            if (array_key_exists('cve', $vulnerability['references'])) {
                                $cves = $vulnerability['references']['cve'];
                                WP_CLI::line(sprintf('CVE\'s: %s', implode(', ', $cves)));
                            }
                            WP_CLI::line(
                                sprintf(
                                    'Fixed in: %s',
                                    empty($vulnerability['fixed_in']) ? 'fix n/a' : $vulnerability['fixed_in']
                                )
                            );

                            break;
                    }
                }
            }


            if ($this->themeVulnerabilityCount === 0) {
                switch ($this->outputType) {
                    case self::OUTPUT_JSON:
                        break;
                    case self::OUTPUT_NAGIOS:
                        break;
                    default:
                        WP_CLI::line('No known theme vulnerabilities found');
                        break;
                }
            }
        }

        /**
         * @param $versionToCheck
         * @param $minimumVersion
         * @return bool
         */
        private function isVersionLessThan($versionToCheck, $minimumVersion)
        {
            $toCheckParts = explode('.', trim($versionToCheck));
            $minimumParts = explode('.', trim($minimumVersion));

            foreach ($toCheckParts as $index => $version) {

                if ($index > (count($minimumParts) - 1)) {
                    throw new \UnexpectedValueException(
                        sprintf('Cant compare %s to %s', $versionToCheck, $minimumVersion)
                    );
                }

                if ($version < $minimumParts[$index]) {
                    return true;
                } elseif ($version > $minimumParts[$index]) {
                    return false;
                }
            }

            return false; // equals to the minimum required version
        }
    }
}


if ( ! class_exists( 'WpSecVersion' ) ) {

    class WpSecVersion
    {
        public function __invoke()
        {
            WP_CLI::line('Version: 0.1-beta');
        }
    }
}

WP_CLI::add_command(
	'wp-sec check',
    'WpSecCheck',
    array(
		'shortdesc' => 'Checks for vulnerabilities at wpvulndb.com',
		'synopsis' => array(
			array(
				'type'     => 'assoc',
				'name'     => 'type',
				'optional' => true,
				'default'  => 'all',
				'options'  => array( 'core', 'themes', 'plugins', 'all'),
			),
            array(
                'type'     => 'assoc',
                'name'     => 'output',
                'optional' => true,
                'default'  => 'user',
                'options'  => array( 'json', 'nagios', 'user'),
            ),
		),
        'when' => 'before_wp_load',
	)
);

WP_CLI::add_command(
    'wp-sec version',
    'WpSecVersion',
    array(
        'shortdesc' => 'Returns current version of wp-sec',
        'when' => 'before_wp_load',
    )
);