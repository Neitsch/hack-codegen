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

/**
 *  Designate machine-generated code so tools can distinguish it from
 *  human-generated code, and prevent manual edits of machine-generated code by
 *  embedding a simple checksum in generated source files.
 *
 *
 *  = Generating Signed Source =
 *
 *  When generating source, use SignedSource to sign the file. This will prevent
 *  it from being checked in if it is manually edited. Signing is a two step
 *  process:
 *
 *    1. Call SignedSource::getSigningToken() and embed the return string
 *       somewhere in your source file (generally, in a header comment).
 *    2. After generating the file, call SignedSource::signFile($file).
 *
 *  For example:
 *
 *    $signature_token = SignedSource::getSigningToken();
 *    $generated_file = <<<EODOC
 *      /**
 *       *  This file is generated. Do not modify it manually!
 *       *
 *       *  {$signature_token}
 *       *
 *      ...
 *
 *    $signed_file = SignedSource::signFile($generated_file);
 *    Filesystem::writeFileIfChanged('/path/to/generated/file', $signed_file);
 *
 *
 *  = Verifying Signed Source =
 *
 *  Use SignedSource::isSigned() to determine if a file has a signature or not.
 *  Then, use SignedSource::verifySignature() to verify a file's signature:
 *
 *    $is_signed = SignedSource::isSigned($questionable_file);
 *    if ($is_signed) {
 *      $intact = SignedSource::verifySignature($questionable_file);
 *      if ($intact) {
 *        echo 'File is signed with correct signature.';
 *      } else {
 *        echo 'File is signed with invalid signature. It has been edited!';
 *      }
 *    }
 *
 */
final class SignedSource extends SignedSourceBase {
  protected static function getTokenName(): string {
    return 'generated';
  }

  /**
   * Get the text for a doc block that can be used for an autogenerated file.
   * If a comment is set, it will be included in the doc block.
   */
  public static function getDocBlock(?string $comment = null): string {
    $comment = \HH\Lib\Str\is_empty($comment) ? null : $comment."\n\n";
    return "This file is generated. Do not modify it manually!\n\n".
      $comment.
      self::getSigningToken();
  }
}
