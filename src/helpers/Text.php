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

namespace nystudio107\seomatic\helpers;

use nystudio107\seomatic\helpers\Field as FieldHelper;

use craft\elements\db\MatrixBlockQuery;
use craft\elements\db\TagQuery;

use nystudio107\seomatic\Seomatic;
use yii\base\InvalidConfigException;

use PhpScience\TextRank\TextRankFacade;
use PhpScience\TextRank\Tool\StopWords\StopWordsAbstract;

/**
 * @author    nystudio107
 * @package   Seomatic
 * @since     3.0.0
 */
class Text
{
    // Constants
    // =========================================================================

    const LANGUAGE_MAP = [
        'en' => 'English',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'no' => 'Norwegian',
        'es' => 'Spanish',
    ];

    // Public Static Methods
    // =========================================================================

    /**
     * Extract plain old text from a field
     *
     * @param $field
     *
     * @return string
     */
    public static function extractTextFromField($field)
    {
        if ($field instanceof MatrixBlockQuery) {
            /** @var MatrixBlockQuery $field */
            $result = self::extractTextFromMatrix($field);
        } elseif ($field instanceof TagQuery) {
            $result = self::extractTextFromTags($field);
        } else {
            $result = strip_tags($field);
        }

        return $result;
    }

    /**
     * Extract concatenated text from all of the tags in the $tagElement and
     * return as a comma-delimited string
     *
     * @param TagQuery $tagQuery
     *
     * @return string
     */
    public static function extractTextFromTags(TagQuery $tagQuery)
    {
        $result = '';
        // Iterate through all of the matrix blocks
        $tags = $tagQuery->all();
        foreach ($tags as $tag) {
            $result .= $tag->title.", ";
        }
        $result = rtrim($result, ", ");

        return $result;
    }

    /**
     * Extract text from all of the blocks in a matrix field, concatenating it
     * together.
     *
     * @param MatrixBlockQuery $matrixQuery
     * @param string           $fieldHandle
     *
     * @return string
     */
    public static function extractTextFromMatrix(MatrixBlockQuery $matrixQuery, $fieldHandle = '')
    {
        $result = '';
        // Iterate through all of the matrix blocks
        $blocks = $matrixQuery->all();
        foreach ($blocks as $block) {
            try {
                $matrixBlockTypeModel = $block->getType();
            } catch (InvalidConfigException $e) {
                $matrixBlockTypeModel = null;
            }
            // Find any text fields inside of the matrix block
            if ($matrixBlockTypeModel) {
                $fieldClasses = FieldHelper::FIELD_CLASSES[FieldHelper::TEXT_FIELD_CLASS_KEY];
                $fields = $matrixBlockTypeModel->getFields();

                foreach ($fields as $field) {
                    foreach ($fieldClasses as $fieldClassKey) {
                        if ($field instanceof $fieldClassKey) {
                            if ($field->handle == $fieldHandle || empty($fieldHandle)) {
                                $result .= self::extractTextFromField($block[$field->handle]).' ';
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Return the most important keywords extracted from the text as a comma-
     * delimited string
     *
     * @param string $text
     * @param int    $limit
     * @param bool   $useStopWords
     *
     * @return string
     */
    public static function extractKeywords(string $text, $limit = 15, $useStopWords = true): string
    {
        if (empty($text)) {
            return '';
        }
        $api = new TextRankFacade();
        // Set the stop words that should be ignored
        if ($useStopWords) {
            $language = strtolower(substr(Seomatic::$language, 0, 2));
            $stopWords = self::stopWordsForLanguage($language);
            if ($stopWords !== null) {
                $api->setStopWords($stopWords);
            }
        }
        // Array of the most important keywords:
        $keywords = $api->getOnlyKeyWords(self::cleanupText($text));

        // If it's empty, just return the text
        if (empty($keywords)) {
            return $text;
        }

        return is_array($keywords)
            ? implode(", ", array_slice(array_keys($keywords), 0, $limit))
            : strval($keywords);
    }

    /**
     * Extract a summary consisting of the 3 most important sentences from the
     * text
     *
     * @param string $text
     * @param bool   $useStopWords
     *
     * @return string
     */
    public static function extractSummary(string $text, $useStopWords = true): string
    {
        if (empty($text)) {
            return '';
        }
        $api = new TextRankFacade();
        // Set the stop words that should be ignored
        if ($useStopWords) {
            $language = strtolower(substr(Seomatic::$language, 0, 2));
            $stopWords = self::stopWordsForLanguage($language);
            if ($stopWords !== null) {
                $api->setStopWords($stopWords);
            }
        }
        // Array of the most important keywords:
        $sentences = $api->getHighlights(self::cleanupText($text));

        // If it's empty, just return the text
        if (empty($sentences)) {
            return $text;
        }

        return is_array($sentences)
            ? implode(" ", $sentences)
            : strval($sentences);
    }

    /**
     * Clean up the passed in text by converting it to UTF-8, stripping tags,
     * removing whitespace, and decoding HTML entities
     *
     * @param string $text
     *
     * @return string
     */
    public static function cleanupText(string $text): string
    {
        // Convert to UTF-8
        if (function_exists('iconv')) {
            $text = iconv(mb_detect_encoding($text, mb_detect_order(), true), "UTF-8//IGNORE", $text);
        } else {
            ini_set('mbstring.substitute_character', "none");
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        // Strip HTML tags
        $text = strip_tags($text);
        // Remove excess whitespace
        $text = preg_replace('/\s{2,}/u', ' ', $text);
        // Decode any HTML entities
        $text = html_entity_decode($text);

        return $text;
    }

    // Protected Static Methods
    // =========================================================================

    /**
     * @param string $language
     *
     * @return null|StopWordsAbstract
     */
    protected static function stopWordsForLanguage(string $language)
    {
        $stopWords = null;
        if (!empty(self::LANGUAGE_MAP[$language])) {
            $language = self::LANGUAGE_MAP[$language];
        } else {
            $language = 'English';
        }

        $className = 'PhpScience\\TextRank\\Tool\\StopWords\\'.ucfirst($language);
        if (class_exists($className)) {
            $stopWords = new $className;
        }

        return $stopWords;
    }
}
