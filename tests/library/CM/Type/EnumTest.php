<?php

class CM_Type_EnumTest extends CMTest_TestCase {

    public function testValid() {
        $circle = new FiguresValidMock('circle');
        $this->assertSame('circle', (string) $circle);

        $this->assertSame(
            [
                'CIRCLE'   => 'circle',
                'SQUARE'   => 'square',
                'TRIANGLE' => 'triangle',
            ],
            FiguresValidMock::getConstantList()
        );

        $default = new FiguresValidMock();
        $this->assertSame('square', (string) $default);
    }

    public function testInvalid() {
        $exception = $this->catchException(function () {
            new FiguresValidMock('bar');
        });
        $this->assertInstanceOf('CM_Exception_Invalid', $exception);
        $this->assertSame('Invalid value `bar` for enum class `FiguresValidMock`', $exception->getMessage());

        $exception = $this->catchException(function () {
            new FiguresInvalidNoDefaultMock();
        });
        $this->assertInstanceOf('CM_Exception_Invalid', $exception);
        $this->assertSame('Default value in not defined for enum class `FiguresInvalidNoDefaultMock`', $exception->getMessage());

        $exception = $this->catchException(function () {
            new FiguresInvalidValidDefaultMock();
        });
        $this->assertInstanceOf('CM_Exception_Invalid', $exception);
        $this->assertSame('Invalid default value for enum class `FiguresInvalidValidDefaultMock`', $exception->getMessage());
    }
}

class FiguresValidMock extends CM_Type_Enum {

    protected function _getDefaultValue() {
        return self::SQUARE;
    }

    const CIRCLE = 'circle';
    const SQUARE = 'square';
    const TRIANGLE = 'triangle';
}

class FiguresInvalidNoDefaultMock extends CM_Type_Enum {

    const SQUARE = 'square';
    const TRIANGLE = 'triangle';
}

class FiguresInvalidValidDefaultMock extends CM_Type_Enum {

    protected function _getDefaultValue() {
        return 'foo';
    }

    const SQUARE = 'square';
    const TRIANGLE = 'triangle';
}
