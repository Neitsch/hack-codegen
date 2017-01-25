<?hh // strict
/**
 * Copyright (c) 2015-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the same directory.
 */

namespace Facebook\HackCodegen;

use function Facebook\HackCodegen\LegacyHelpers\hack_builder;

final class HackBuilderTest extends CodegenBaseTest {

  public function testIfBlock(): void {
    $body = test_code_builder()
      ->startIfBlock('$value <= %d', 0)
      ->addLine('return 0;')
      ->addElseIfBlock('$value === %d', 1)
      ->addLine('return 1;')
      ->addElseBlock()
      ->addLine('return 2;')
      ->endIfBlock();
    $this->assertUnchanged($body->getCode());
  }

  public function testForeachLoop(): void {
    $body = test_code_builder()
      ->startForeachLoop('$values', null, '$value')
      ->addLine('something($value);')
      ->endForeachLoop()
      ->startForeachLoop('$values', '$idx', '$value')
      ->addLine('$values[$idx] = $value + 1;')
      ->endForeachLoop();
    $this->assertUnchanged($body->getCode());
  }

  public function testTryBLock(): void {
    $body = test_code_builder()
      ->startTryBlock()
      ->addLine('my_func();')
      ->addCatchBlock('SystemException', '$ex')
      ->addLine('return null;')
      ->addFinallyBlock()
      ->addLine('bump_ods();')
      ->endTryBlock();
    $this->assertUnchanged($body->getCode());
  }

  public function testDocBlock(): void {
    $comment = 'Wow a really long comment that '.
      'will span multiple lines and probably go over '.
      'the limit so we gotta cut it up.';
    $body = hack_builder()
      ->addDocBlock($comment);
    $this->assertUnchanged($body->getCode());

    $body = hack_builder()
      ->addDocBlock($comment, /* max len */ 50);
    $this->assertUnchanged($body->getCode(), 'docblock2');
  }

  public function testShape(): void {
    $shape = test_code_builder()
      ->addShape(array('x' => 3, 'y' => 5, 'url' => 'www.facebook.com'));

    $this->assertUnchanged($shape->getCode());
  }

  public function testWrappedStringSingle(): void {
    $this->assertUnchanged(
      test_code_builder()
        ->add('return ')
        ->addWrappedString('This is short')
        ->add(';')
        ->getCode(),
    );
  }

  public function testWrappedStringDouble(): void {
    $this->assertUnchanged(
      test_code_builder()
        ->add('return ')
        ->addWrappedString('This is a bit longer so we will hit our max '.
          'length cap and then go ahead and finish the line.')
        ->add(';')
        ->getCode(),
    );
  }

  public function testWrappedStringMulti(): void {
    $lorem_ipsum = 'So here is a super long string that will wrap past the
two line breaks. Also note that we include a newline and also '.
      'do a concat operation to really mix it up. We need to
      respect newlines with this code and also senseless indentation.';
    $this->assertUnchanged(
      test_code_builder()
        ->add('return ')
        ->addWrappedString($lorem_ipsum)
        ->add(';')
        ->getCode(),
    );
  }

  public function testWrappedStringDoNotIndent(): void {
    $this->assertUnchanged(
      test_code_builder()
        ->add('$this->callMethod(')
        ->newLine()
        ->indent()
        ->addWrappedStringNoIndent(
          'This string is quite long so will spread across three lines but '.
          'the user wants it to look like just in this piece of code (second '.
          'line indentation matches the first one)',
          null,
        )
        ->unindent()
        ->newLine()
        ->add(');')
        ->getCode(),
    );
  }

  public function testSet(): void {
    $set = test_code_builder()
      ->addSet(Set {'apple', 'oreos', 'banana'});

    $this->assertUnchanged($set->getCode());
  }

  public function testAddWithSuggestedLineBreaksNoBreakage(): void {
    $del = HackBuilder::DELIMITER;
    $body = test_code_builder()->addWithSuggestedLineBreaks(
      "final class{$del}ClassNameJustLongEnoughToAvoidEightyColumns{$del}".
      "extends SomeBaseClass",
    );
    $this->assertUnchanged($body->getCode());
  }

  public function testAddWithSuggestedLineBreaksWithBreakage(): void {
    $del = HackBuilder::DELIMITER;
    $body = test_code_builder()->addWithSuggestedLineBreaks(
      "final abstract class{$del}ImpossibleClassLongEnoughToCrossEightyColumns".
      "{$del}extends SomeBaseClass",
    );
    $this->assertUnchanged($body->getCode());
  }

  public function testAddfWithSuggestedLineBreaks(): void {
    $code = test_code_builder()->addWithSuggestedLineBreaksf(
      "%s\n%s",
      'foo',
      'bar',
    )->getCode();
    $this->assertSame("foo\nbar", $code);
  }

  public function testAddSmartMultilineCall(): void {
    $del = HackBuilder::DELIMITER;
    $body = test_code_builder()->addMultilineCall(
      "\$foobarbaz_alphabetagama ={$del}\$this->callSomeThingReallyLongName".
      "ReallyReallyLongName",
      Vector {
        '$someSmallParameter',
        "\$foobarbaz_alphabetagama +{$del}\$foobarbaz_alphabetagamaa +{$del}".
        "\$foobarbaz_alphabetagamatheta_foobarbaz",
      },
    );
    $this->assertUnchanged($body->getCode());
  }

  public function testLiteralMap(): void {
    $body = test_code_builder()
      ->addMap(
        Map {
          'MY_ENUM::A' => 'ANOTHER_ENUM::A',
          'MY_ENUM::B' => 'ANOTHER_ENUM::B'
        },
        HackBuilderKeys::literal(),
        HackBuilderValues::literal(),
      );
    $this->assertUnchanged($body->getCode());
  }

  public function testAnotherConfig(): void {
    $body = (new HackBuilder(new TestAnotherCodegenConfig()))
      ->addInlineComment(
        "Here we wrap at 40 chars because we use a different configuration."
      )
      ->startIfBlock('$do_that')
      ->add('return ')
      ->addArray(array(1, 2, 3))
      ->closeStatement()
      ->endIfBlock();

    $this->assertUnchanged($body->getCode());
  }

  public function testSwitchBodyWithReturnsInCaseAndDefault(): void {
    // Gosh, I have no idea what names of football shots are!
    $players = Vector {
      array('name' => 'Ronaldo', 'favorite_shot' => 'freeKick'),
      array('name' => 'Messi', 'favorite_shot' => 'slideKick'),
      array('name' => 'Maradona', 'favorite_shot' => 'handOfGod'),
    };

    $body = hack_builder()
      ->startSwitch('$soccer_player')
      ->addCaseBlocks(
        $players,
        ($player, $body) ==> {
          $body->addCase(sprintf('\'%s\'', $player['name']))
            ->addLinef('$shot = new Shot(\'%s\');', $player['favorite_shot'])
            ->returnCase('$shot->execute()');
        },
      )
      ->addDefault()
      ->addLine('invariant_violation(\'ball deflated!\');')
      ->endDefault()
      ->endSwitch_();
    $this->assertUnchanged($body->getCode());
  }

  public function testSwitchBodyWithBreaksInCaseAndDefault(): void {
    // Gosh, I have no idea what names of football shots are!
    $players = Vector {
      array('name' => 'Ronaldo', 'favorite_shot' => 'freeKick'),
      array('name' => 'Messi', 'favorite_shot' => 'slideKick'),
      array('name' => 'Maradona', 'favorite_shot' => 'handOfGod'),
    };

    $body = hack_builder()
      ->startSwitch('$soccer_player')
      ->addCaseBlocks(
        $players,
        ($player, $body) ==> {
          $body->addCase(sprintf('\'%s\'', $player['name']))
            ->addLinef('$shot = new Shot(\'%s\');', $player['favorite_shot'])
            ->breakCase();
        },
      )
      ->addDefault()
      ->addLine('invariant_violation(\'ball deflated!\');')
      ->endDefault()
      ->endSwitch_();
    $this->assertUnchanged($body->getCode());
  }

  public function testSwitchBodyWithMultipleCasesWithoutBreaks(): void {
    // Gosh, I have no idea what names of football shots are!
    $players = Vector {
      array('name' => 'Ronaldo', 'favorite_shot' => 'freeKick'),
      array('name' => 'Messi', 'favorite_shot' => 'slideKick'),
      array('name' => 'Maradona', 'favorite_shot' => 'handOfGod'),
    };

    $body = hack_builder()
      ->startSwitch('$soccer_player')
      ->addCaseBlocks(
        $players,
        ($player, $body) ==> {
          $body->addCase(sprintf('\'%s\'', $player['name']))
            ->addLinef('$shot = new Shot(\'%s\');', $player['favorite_shot'])
            ->unindent();
        },
      )
      ->addDefault()
      ->addLine('invariant_violation(\'ball deflated!\');')
      ->endDefault()
      ->endSwitch_();
    $this->assertUnchanged($body->getCode());
  }
}

final class TestAnotherCodegenConfig extends HackCodegenConfig {
  public function getFileHeader(): ?Vector<string> {
    return Vector {'Codegen Tests'};
  }

  public function getSpacesPerIndentation(): int {
    return 4;
  }

  public function getMaxLineLength(): int {
    return 40;
  }

  public function getRootDir(): string {
    return '/';
  }
}