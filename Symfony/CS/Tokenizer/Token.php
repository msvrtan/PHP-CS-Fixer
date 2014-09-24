<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Tokenizer;

/**
 * Representation of single token.
 * As a token prototype you should understand a single element generated by token_get_all.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class Token
{
    /**
     * Content of token prototype.
     *
     * @var string
     */
    private $content;

    /**
     * ID of token prototype, if available.
     *
     * @var int|null
     */
    private $id;

    /**
     * If token prototype is an array.
     *
     * @var bool
     */
    private $isArray;

    /**
     * Line of token prototype occurrence, if available.
     *
     * @var int|null
     */
    private $line;

    /**
     * Constructor.
     *
     * @param string|array $token token prototype
     */
    public function __construct($token)
    {
        if (is_array($token)) {
            $this->isArray = true;
            $this->id = $token[0];
            $this->content = $token[1];
            $this->line = isset($token[2]) ? $token[2] : null;
        } else {
            $this->isArray = false;
            $this->content = $token;
        }
    }

    /**
     * Clear token at given index.
     *
     * Clearing means override token by empty string.
     */
    public function clear()
    {
        $this->override('');
    }

    /*
     * Check if token is equals to given one.
     *
     * If tokens are arrays, then only keys defined in parameter token are checked.
     *
     * @param Token|array|string $other token or it's prototype
     *
     * @return bool
     */
    public function equals($other)
    {
        $otherPrototype = $other instanceof Token ? $other->getPrototype() : $other;

        if ($this->isArray() !== is_array($otherPrototype)) {
            return false;
        }

        if (!$this->isArray()) {
            return $this->content === $otherPrototype;
        }

        $selfPrototype = $this->getPrototype();

        foreach ($otherPrototype as $key => $val) {
            if ($selfPrototype[$key] !== $val) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if token is equals to one of given.
     *
     * @param array $others array of tokens or token prototypes
     *
     * @return bool
     */
    public function equalsAny(array $others)
    {
        foreach ($others as $other) {
            if ($this->equals($other)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get token prototype.
     *
     * @return string|array token prototype
     */
    public function getPrototype()
    {
        if (!$this->isArray) {
            return $this->content;
        }

        return array(
            $this->id,
            $this->content,
            $this->line,
        );
    }

    /**
     * Get token's content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get token's id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get token's line.
     *
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Get token name.
     *
     * @return null|string token name
     */
    public function getName()
    {
        if (!isset($this->id)) {
            return;
        }

        $transformers = Transformers::create();

        if ($transformers->hasCustomToken($this->id)) {
            return $transformers->getCustomToken($this->id);
        }

        return token_name($this->id);
    }

    /**
     * Generate keywords array contains all keywords that exists in used PHP version.
     *
     * @return array
     */
    public static function getKeywords()
    {
        static $keywords = null;

        if (null === $keywords) {
            $keywords = array();
            $keywordsStrings = array('T_ABSTRACT', 'T_ARRAY', 'T_AS', 'T_BREAK', 'T_CALLABLE', 'T_CASE',
                'T_CATCH', 'T_CLASS', 'T_CLONE', 'T_CONST', 'T_CONTINUE', 'T_DECLARE', 'T_DEFAULT', 'T_DO',
                'T_ECHO', 'T_ELSE', 'T_ELSEIF', 'T_EMPTY', 'T_ENDDECLARE', 'T_ENDFOR', 'T_ENDFOREACH',
                'T_ENDIF', 'T_ENDSWITCH', 'T_ENDWHILE', 'T_EVAL', 'T_EXIT', 'T_EXTENDS', 'T_FINAL',
                'T_FINALLY', 'T_FOR', 'T_FOREACH', 'T_FUNCTION', 'T_GLOBAL', 'T_GOTO', 'T_HALT_COMPILER',
                'T_IF', 'T_IMPLEMENTS', 'T_INCLUDE', 'T_INCLUDE_ONCE', 'T_INSTANCEOF', 'T_INSTEADOF',
                'T_INTERFACE', 'T_ISSET', 'T_LIST', 'T_LOGICAL_AND', 'T_LOGICAL_OR', 'T_LOGICAL_XOR',
                'T_NAMESPACE', 'T_NEW', 'T_PRINT', 'T_PRIVATE', 'T_PROTECTED', 'T_PUBLIC', 'T_REQUIRE',
                'T_REQUIRE_ONCE', 'T_RETURN', 'T_STATIC', 'T_SWITCH', 'T_THROW', 'T_TRAIT', 'T_TRY',
                'T_UNSET', 'T_USE', 'T_VAR', 'T_WHILE', 'T_YIELD',
            );

            foreach ($keywordsStrings as $keywordName) {
                if (defined($keywordName)) {
                    $keyword = constant($keywordName);
                    $keywords[$keyword] = $keyword;
                }
            }
        }

        return $keywords;
    }

    /**
     * Check if token prototype is an array.
     *
     * @return bool is array
     */
    public function isArray()
    {
        return $this->isArray;
    }

    /**
     * Check if token is one of type cast tokens.
     *
     * @return bool
     */
    public function isCast()
    {
        static $castTokens = array(T_ARRAY_CAST, T_BOOL_CAST, T_DOUBLE_CAST, T_INT_CAST, T_OBJECT_CAST, T_STRING_CAST, T_UNSET_CAST);

        return $this->isGivenKind($castTokens);
    }

    /**
     * Check if token is one of classy tokens: T_CLASS, T_INTERFACE or T_TRAIT.
     *
     * @return bool
     */
    public function isClassy()
    {
        static $classTokens = null;

        if (null === $classTokens) {
            $classTokens = array(T_CLASS, T_INTERFACE);

            if (defined('T_TRAIT')) {
                $classTokens[] = constant('T_TRAIT');
            }
        }

        return $this->isGivenKind($classTokens);
    }

    /**
     * Check if token is one of comment tokens: T_COMMENT or T_DOC_COMMENT.
     *
     * @return bool
     */
    public function isComment()
    {
        static $commentTokens = array(T_COMMENT, T_DOC_COMMENT);

        return $this->isGivenKind($commentTokens);
    }

    /**
     * Check if token is empty, e.g. because of clearing.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return null === $this->id && ('' === $this->content || null === $this->content);
    }

    /**
     * Check if token is one of given kind.
     *
     * @param int|array $possibleKind kind or array of kinds
     *
     * @return bool
     */
    public function isGivenKind($possibleKind)
    {
        return $this->isArray && (is_array($possibleKind) ? in_array($this->id, $possibleKind, true) : $this->id === $possibleKind);
    }

    /**
     * Check if token is a keyword.
     *
     * @return bool
     */
    public function isKeyword()
    {
        $keywords = static::getKeywords();

        return $this->isArray && isset($keywords[$this->id]);
    }

    /**
     * Check if token is a native PHP constant: true, false or null.
     *
     * @return bool
     */
    public function isNativeConstant()
    {
        static $nativeConstantStrings = array('true', 'false', 'null');

        return $this->isArray && in_array(strtolower($this->content), $nativeConstantStrings, true);
    }

    /**
     * Check if token is one of structure alternative end syntax (T_END...)
     *
     * @return bool
     */
    public function isStructureAlternativeEnd()
    {
        static $commentTokens = array(T_ENDDECLARE, T_ENDFOR, T_ENDFOREACH, T_ENDIF, T_ENDSWITCH, T_ENDWHILE, T_END_HEREDOC);

        return $this->isGivenKind($commentTokens);
    }

    /**
     * Check if token is a whitespace.
     *
     * @param array  $opts                array of extra options
     * @param string $opts['whitespaces'] string determining whitespaces chars, default is " \t\n\r\0\x0B"
     *
     * @return bool
     */
    public function isWhitespace(array $opts = array())
    {
        $whitespaces = isset($opts['whitespaces']) ? $opts['whitespaces'] : " \t\n\r\0\x0B";

        if ($this->isArray && !$this->isGivenKind(T_WHITESPACE)) {
            return false;
        }

        return '' === trim($this->content, $whitespaces);
    }

    /**
     * Override token.
     *
     * @param string|array $prototype token prototype
     */
    public function override($prototype)
    {
        if (is_array($prototype)) {
            $this->isArray = true;
            $this->id = $prototype[0];
            $this->content = $prototype[1];
            $this->line = isset($prototype[2]) ? $prototype[2] : null;

            return;
        }

        $this->isArray = false;
        $this->id = null;
        $this->content = $prototype;
        $this->line = null;
    }

    /**
     * Set token's content.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->getName(),
            'content' => $this->content,
            'line' => $this->line,
            'isArray' => $this->isArray,
        );
    }

    public function toJSON()
    {
        static $optNames = array('JSON_PRETTY_PRINT', 'JSON_NUMERIC_CHECK');

        $options = 0;

        foreach ($optNames as $optName) {
            if (defined($optName)) {
                $options |= constant($optName);
            }
        }

        return json_encode($this->toArray(), $options);
    }
}
