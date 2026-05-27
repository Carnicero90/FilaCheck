<?php

use Filacheck\Rules\DeprecatedImageEditorAspectRatiosRule;

it('detects imageEditorAspectRatios on FileUpload', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\FileUpload;

class TestResource
{
    public function form(): array
    {
        return [
            FileUpload::make('image')
                ->image()
                ->imageEditor()
                ->imageEditorAspectRatios(['16:9', '4:3']),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageEditorAspectRatiosRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('imageEditorAspectRatios()', $violations);
});

it('detects imageEditorAspectRatios on SpatieMediaLibraryFileUpload', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class TestResource
{
    public function form(): array
    {
        return [
            SpatieMediaLibraryFileUpload::make('media')
                ->image()
                ->imageEditor()
                ->imageEditorAspectRatios(['16:9']),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageEditorAspectRatiosRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('imageEditorAspectRatios()', $violations);
});

it('passes when imageEditorAspectRatioOptions is used', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\FileUpload;

class TestResource
{
    public function form(): array
    {
        return [
            FileUpload::make('image')
                ->image()
                ->imageEditor()
                ->imageEditorAspectRatioOptions(['16:9', '4:3']),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageEditorAspectRatiosRule, $code);

    $this->assertNoViolations($violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            FileUpload::make('image')->imageEditorAspectRatios(['16:9']),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageEditorAspectRatiosRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes imageEditorAspectRatios to imageEditorAspectRatioOptions', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            FileUpload::make('image')->imageEditorAspectRatios(['16:9', '4:3']),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedImageEditorAspectRatiosRule, $code);

    expect($fixedCode)->toContain("->imageEditorAspectRatioOptions(['16:9', '4:3'])");
    expect($fixedCode)->not->toContain('->imageEditorAspectRatios(');
});

it('does not flag imageEditorAspectRatios on unrelated components', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('whatever')->imageEditorAspectRatios(['16:9']),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageEditorAspectRatiosRule, $code);

    $this->assertNoViolations($violations);
});

it('detects $this->imageEditorAspectRatios() in a class extending FileUpload', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\FileUpload;

class AvatarUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->image()
            ->imageEditor()
            ->imageEditorAspectRatios(['1:1']);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageEditorAspectRatiosRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('imageEditorAspectRatios()', $violations);
});

it('detects $this->imageEditorAspectRatios() in a class extending SpatieMediaLibraryFileUpload', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class MediaUpload extends SpatieMediaLibraryFileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->imageEditorAspectRatios(['16:9']);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageEditorAspectRatiosRule, $code);

    $this->assertViolationCount(1, $violations);
});

it('ignores $this->imageEditorAspectRatios() in a class that does not extend FileUpload', function () {
    $code = <<<'PHP'
<?php

class SomeComponent extends Component
{
    protected function setUp(): void
    {
        $this->imageEditorAspectRatios(['16:9']);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageEditorAspectRatiosRule, $code);

    $this->assertNoViolations($violations);
});

it('fixes multiple imageEditorAspectRatios usages', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            FileUpload::make('avatar')->imageEditorAspectRatios(['1:1']),
            FileUpload::make('banner')->imageEditorAspectRatios(['16:9']),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedImageEditorAspectRatiosRule, $code);

    expect(substr_count($fixedCode, '->imageEditorAspectRatioOptions('))->toBe(2);
    expect($fixedCode)->not->toContain('->imageEditorAspectRatios(');
});
