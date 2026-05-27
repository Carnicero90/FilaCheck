<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Rules\Concerns\ResolvesFilamentDocsUrl;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class DeprecatedImageEditorAspectRatiosRule implements FixableRule, ProvidesAgentFix
{
    use CalculatesLineNumbers;
    use ResolvesFilamentDocsUrl;

    public function name(): string
    {
        return 'deprecated-image-editor-aspect-ratios';
    }

    public function category(): RuleCategory
    {
        return RuleCategory::Deprecated;
    }

    public function check(Node $node, Context $context): array
    {
        if (! $node instanceof MethodCall) {
            return [];
        }

        if (! $node->name instanceof Identifier) {
            return [];
        }

        if ($node->name->name !== 'imageEditorAspectRatios') {
            return [];
        }

        if (! $this->isFileUploadContext($node, $context)) {
            return [];
        }

        $nameNode = $node->name;
        $startPos = $nameNode->getStartFilePos();
        $endPos = $nameNode->getEndFilePos() + 1;

        return [
            new Violation(
                level: 'warning',
                message: 'The `imageEditorAspectRatios()` method is deprecated on FileUpload.',
                file: $context->file,
                line: $this->getLineFromPosition($context->code, $startPos),
                suggestion: 'Use `imageEditorAspectRatioOptions()` instead. See: '.$this->filamentDocsUrl('forms/file-upload#allowing-users-to-crop-images-to-aspect-ratios'),
                isFixable: true,
                startPos: $startPos,
                endPos: $endPos,
                replacement: 'imageEditorAspectRatioOptions',
            ),
        ];
    }

    public function agentFix(Violation $violation): mixed
    {
        return [
            'instructions' => 'Rename the deprecated `imageEditorAspectRatios()` method on `FileUpload` (and `SpatieMediaLibraryFileUpload`) to `imageEditorAspectRatioOptions()`.',
            'next_steps' => [
                'Replace `->imageEditorAspectRatios(...)` with `->imageEditorAspectRatioOptions(...)`. Arguments are unchanged.',
                'Apply this rename on `FileUpload` and `SpatieMediaLibraryFileUpload` chains only.',
            ],
            'docs' => $this->filamentDocsUrl('forms/file-upload#allowing-users-to-crop-images-to-aspect-ratios'),
        ];
    }

    private function isFileUploadContext(MethodCall $node, Context $context): bool
    {
        $current = $node->var;

        while ($current instanceof MethodCall) {
            $current = $current->var;
        }

        if ($current instanceof StaticCall && $current->class instanceof Name) {
            $parts = explode('\\', $current->class->toString());

            return in_array(end($parts), ['FileUpload', 'SpatieMediaLibraryFileUpload'], true);
        }

        if ($current instanceof Variable && $current->name === 'this') {
            return $this->isInsideFileUploadClass($context);
        }

        return false;
    }

    private function isInsideFileUploadClass(Context $context): bool
    {
        if (! preg_match('/class\s+\w+\s+extends\s+(\w+)/', $context->code, $matches)) {
            return false;
        }

        return in_array($matches[1], ['FileUpload', 'SpatieMediaLibraryFileUpload'], true);
    }
}
