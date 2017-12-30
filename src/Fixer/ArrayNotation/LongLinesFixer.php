<?php

namespace PhpCsFixer\Fixer\ArrayNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

final class LongLinesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    /**
     * @TODO:
     */
    const LIMIT = 120;

    /** @var string Configured indent */
    private $indent;

    public function getPriority()
    {
        return 100;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'PHP multi-line arrays should have a trailing comma.',
            [new CodeSample("<?php\narray(\n    1,\n    2\n);\n")]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return true;

        return $tokens->isAnyTokenKindsFound([T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $this->indent = $this->whitespacesConfig->getIndent();

        $tokensAnalyzer = new TokensAnalyzer($tokens);

        for ($index = 0; $index <= $tokens->count() - 1; $index++) {
            if ($tokensAnalyzer->isArray($index)) {
                if ($tokensAnalyzer->isArrayMultiLine($index)) {
                    $this->compressMultilineArray($tokens, $index);
                } else {
                    $this->decompressArray($tokens, $index);
                }
            } elseif ($tokens[$index]->equals('(')) {
                $this->fixCall($tokens, $index);
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int    $index
     */
    private function compressMultilineArray(Tokens $tokens, $index)
    {
        $baseIndentation = $this->indent;

        // Calculate array length

        $startIndex = $index;

        if ($tokens[$startIndex]->isGivenKind(T_ARRAY)) {
            $startIndex = $tokens->getNextTokenOfKind($startIndex, ['(']);
            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startIndex);
        } else {
            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $startIndex);
        }

        // Calculate line lenght before the token
        $lineLengthBefore = 0;
        for ($i = $startIndex - 1; $i > 0; $i--) {

            if ($tokens[$i]->isWhitespace()) {
                $newLineCharacterPosition = strrpos($tokens[$i]->getContent(), PHP_EOL);
                // If whitespace has no new line character, we count it as length 1
                if (false === $newLineCharacterPosition) {
                    $lineLengthBefore += 1;
                } else {
                    $baseIndentation = substr($tokens[$i]->getContent(), $newLineCharacterPosition + 1);
                    $lineLengthBefore += strlen($baseIndentation);
                    break;
                }

            } else {
                $lineLengthBefore += strlen($tokens[$i]->getContent());
            }
        }

        // Initial length of the array.
        $initialLength = 0;

        for ($i = $startIndex; $i <= $endIndex; ++$i) {
            // Calculate all whitespaces as length of 1 (we dont want to calculate in indentation)
            if ($tokens[$i]->isWhitespace()) {
                $initialLength += 1;
            } else {
                $initialLength += strlen($tokens[$i]->getContent());
            }
        }

        // Skip if length is over the limit
        if ($lineLengthBefore + $initialLength > self::LIMIT) {
            return;
        }

        for ($i = $startIndex + 1; $i <= $endIndex; ++$i) {

            if ($tokens[$i]->isWhitespace()) {
                $tokens[$i] = new Token([T_WHITESPACE, ' ']);
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int    $index
     */
    private function decompressArray(Tokens $tokens, $index)
    {

        $startIndex = $index;

        if ($tokens[$startIndex]->isGivenKind(T_ARRAY)) {
            $startIndex = $tokens->getNextTokenOfKind($startIndex, ['(']);
            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startIndex);
        } else {
            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $startIndex);
        }

        // Calculate line lenght before the token
        $lineLengthBefore = 0;
        for ($i = $startIndex - 1; $i > 0; $i--) {

            if ($tokens[$i]->isWhitespace()) {
                $newLineCharacterPosition = strrpos($tokens[$i]->getContent(), PHP_EOL);
                // If whitespace has no new line character, we count it as length 1
                if (false === $newLineCharacterPosition) {
                    $lineLengthBefore += 1;
                } else {
                    $baseIndentation = substr($tokens[$i]->getContent(), $newLineCharacterPosition + 1);
                    $lineLengthBefore += strlen($baseIndentation);
                    break;
                }

            } else {
                $lineLengthBefore += strlen($tokens[$i]->getContent());
            }
        }

        // Initial length of the array.
        $initialLength = 0;

        for ($i = $startIndex; $i <= $endIndex; ++$i) {
            $initialLength += strlen($tokens[$i]->getContent());
        }

        // Skip if length is under the limit
        if ($lineLengthBefore + $initialLength < self::LIMIT) {
            return;
        }

        // By default, base indentation should be equal to default one.
        $baseIndentation = $this->indent;

        // Find previous indentation to determine if base indentation should be different then the default one.
        for ($i = $startIndex; $i > 0; --$i) {
            // Look for previous whitespace
            if ($tokens[$i]->isWhitespace()) {
                // Find last occurance of new line in whitespace (whitespace can be multiline!)
                $newLineCharacterPosition = strrpos($tokens[$i]->getContent(), PHP_EOL);
                // If whitespace has new line,
                if (false !== $newLineCharacterPosition) {
                    // Define base indentation as string from new line character all the way to the end
                    $baseIndentation = substr($tokens[$i]->getContent(), $newLineCharacterPosition + 1);
                    break;
                }
            }
        }

        $newLine = PHP_EOL.$baseIndentation.$this->indent;

        //
        // Add new line to start of the array
        //
        if (true === $tokens[$startIndex + 1]->isWhitespace()) {
            $tokens[$startIndex + 1] = new Token([T_WHITESPACE, $newLine]);
        } else {
            $tokens->insertAt($startIndex + 1, new Token([T_WHITESPACE, $newLine]));
            $endIndex++;
        }

        //
        // Add new line to end of the array
        // Indentation should be of same width as it is on the line where array was started.
        //
        if (true === $tokens[$endIndex - 1]->isWhitespace()) {
            $tokens[$endIndex - 1] = new Token([T_WHITESPACE, PHP_EOL.$baseIndentation]);
        } else {
            $tokens->insertAt($endIndex, new Token([T_WHITESPACE, PHP_EOL.$baseIndentation]));
            ++$endIndex;
        }

        $beforeEndIndex = $tokens->getPrevMeaningfulToken($endIndex);

        if ($tokens[$beforeEndIndex]->equals(',')) {
            $beforeEndIndex = $tokens->getPrevMeaningfulToken($beforeEndIndex);
        }

        if ($startIndex === $beforeEndIndex) {
            return;
        }

        for ($i = $startIndex + 1; $i <= $beforeEndIndex; ++$i) {

            // Skip class instantiations and sub arrays
            if (true === $tokens[$i]->isGivenKind(T_NEW)) {
                $openingClassBrace = $tokens->getNextTokenOfKind($i, ['(']);
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingClassBrace);
            } elseif ($tokens[$i]->equals('(')) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $i);
            } elseif ($tokens[$i]->isGivenKind(T_ARRAY)) {
                $openingArrayBrace = $tokens->getNextTokenOfKind($i, ['(']);
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingArrayBrace);
            } elseif ($tokens[$i]->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $i);
            }

            if ($tokens[$i]->equals(',')) {
                if ($tokens[$i + 1]->isWhitespace()) {
                    $tokens[$i + 1] = new Token([T_WHITESPACE, $newLine]);
                } else {
                    $tokens->insertAt($i + 1, new Token([T_WHITESPACE, $newLine]));
                    ++$beforeEndIndex;
                }
            }
        }

    }

    /**
     * @param Tokens $tokens
     * @param int    $index
     */
    private function fixCall(Tokens $tokens, $index)
    {
        $startIndex = $index;

        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startIndex);

        // Calculate line lenght before the token
        $lineLengthBefore = 0;
        for ($i = $startIndex - 1; $i > 0; $i--) {

            if ($tokens[$i]->isWhitespace()) {
                $newLineCharacterPosition = strrpos($tokens[$i]->getContent(), PHP_EOL);
                // If whitespace has no new line character, we count it as length 1
                if (false === $newLineCharacterPosition) {
                    $lineLengthBefore += 1;
                } else {
                    $baseIndentation = substr($tokens[$i]->getContent(), $newLineCharacterPosition + 1);
                    $lineLengthBefore += strlen($baseIndentation);
                    break;
                }

            } else {
                $lineLengthBefore += strlen($tokens[$i]->getContent());
            }
        }

        // Initial length of the call.
        $initialLength = 0;

        for ($i = $startIndex; $i <= $endIndex; ++$i) {
            $initialLength += strlen($tokens[$i]->getContent());
        }

        if ($lineLengthBefore + $initialLength < self::LIMIT) {
            $this->compressCall($tokens, $startIndex, $endIndex);
        } else {
            $this->decompressCall($tokens, $startIndex, $endIndex);
        }

    }

    private function compressCall($tokens, $startIndex, $endIndex)
    {

        for ($i = $startIndex + 1; $i <= $endIndex; ++$i) {

            if ($tokens[$i]->isWhitespace()) {
                $tokens[$i] = new Token([T_WHITESPACE, ' ']);
            }
        }

    }

    private function decompressCall($tokens, $startIndex, $endIndex)
    {

        // By default, base indentation should be equal to default one.
        $baseIndentation = $this->indent;

        // Find previous indentation to determine if base indentation should be different then the default one.
        for ($i = $startIndex; $i > 0; --$i) {
            // Look for previous whitespace
            if ($tokens[$i]->isWhitespace()) {
                $newLineCharacterPosition = strrpos($tokens[$i]->getContent(), PHP_EOL);
                // If whitespace has new line,
                if (false !== $newLineCharacterPosition) {
                    // Define base indentation as string from new line character all the way to the end
                    $baseIndentation = substr($tokens[$i]->getContent(), $newLineCharacterPosition + 1);
                    break;
                }
            }
        }

        $newLine = PHP_EOL.$baseIndentation.$this->indent;

        //
        // Add new line to start of the call
        //
        if (true === $tokens[$startIndex + 1]->isWhitespace()) {
            $tokens[$startIndex + 1] = new Token([T_WHITESPACE, $newLine]);
        } else {
            $tokens->insertAt($startIndex + 1, new Token([T_WHITESPACE, $newLine]));
            ++$endIndex;
        }

        //
        // Add new line to end of the call
        // Indentation should be of same width as it is on the line where call was started.
        //
        if (true === $tokens[$endIndex - 1]->isWhitespace()) {
            $tokens[$endIndex - 1] = new Token([T_WHITESPACE, PHP_EOL.$baseIndentation]);
        } else {
            $tokens->insertAt($endIndex, new Token([T_WHITESPACE, PHP_EOL.$baseIndentation]));
            ++$endIndex;
        }

        for ($i = $startIndex + 1; $i <= $endIndex; ++$i) {
            // Skip class instantiations and sub arrays
            if (true === $tokens[$i]->isGivenKind(T_NEW)) {
                $openingClassBrace = $tokens->getNextTokenOfKind($i, ['(']);
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingClassBrace);
            } elseif ($tokens[$i]->equals('(')) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $i);
            } elseif ($tokens[$i]->isGivenKind(T_ARRAY)) {
                $openingArrayBrace = $tokens->getNextTokenOfKind($i, ['(']);
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingArrayBrace);
            } elseif ($tokens[$i]->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $i);
            }

            if ($tokens[$i]->equals(',')) {
                if ($tokens[$i + 1]->isWhitespace()) {
                    $tokens[$i + 1] = new Token([T_WHITESPACE, $newLine]);
                } else {
                    $tokens->insertAt($i + 1, new Token([T_WHITESPACE, $newLine]));
                    ++$endIndex;
                }
            }
        }
    }

}

