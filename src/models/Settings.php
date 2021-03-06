<?php
/**
 * SEOmatic plugin for Craft CMS 3.x
 *
 * A turnkey SEO implementation for Craft CMS that is comprehensive, powerful,
 * and flexible
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\seomatic\models;

use nystudio107\seomatic\base\FluentModel;

/**
 * @author    nystudio107
 * @package   Seomatic
 * @since     3.0.0
 */
class Settings extends FluentModel
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The public-facing name of the plugin
     */
    public $pluginName = 'SEOmatic';

    /**
     * @var bool Should SEOmatic render metadata?
     */
    public $renderEnabled = true;

    /**
     * @var string The server environment, either `live`, `staging`, or `local`
     */
    public $environment = 'live';

    /**
     * @var bool Should SEOmatic display the SEO Preview sidebar?
     */
    public $displayPreviewSidebar = true;

    /**
     * @var bool Should SEOmatic display the SEO Analysis sidebar?
     */
    public $displayAnalysisSidebar = true;

    /**
     * @var string If `devMode` is on, prefix the <title> with this string
     */
    public $devModeTitlePrefix = '[devMode] ';

    /**
     * @var string The separator character to use for the `<title>` tag
     */
    public $separatorChar = '|';

    /**
     * @var int The max number of characters in the `<title>` tag
     */
    public $maxTitleLength = 120;

    /**
     * @var int The max number of characters in the `<meta name="description">` tag
     */
    public $maxDescriptionLength = 300;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['pluginName', 'string'],
            ['pluginName', 'default', 'value' => 'SEOmatic'],
            ['environment', 'string'],
            ['environment', 'default', 'value' => 'live'],
            ['environment', 'in', 'range' => [
                'live',
                'staging',
                'production',
            ]],
            ['devModeTitlePrefix', 'string'],
            ['devModeTitlePrefix', 'default', 'value' => '[devMode] '],
            ['maxTitleLength', 'integer', 'min' => 10],
            ['maxTitleLength', 'default', 'value' => 120],
            ['maxDescriptionLength', 'integer', 'min' => 10],
            ['maxDescriptionLength', 'default', 'value' => 300],
        ];
    }
}
