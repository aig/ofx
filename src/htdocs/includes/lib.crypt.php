<?php
/*
 * Copyright (c) AIG
 * aignospam at gmail.com
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY AUTHOR AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL AUTHOR OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

function encodePassword($password) {
  for ($i = 0; $i < 32; $i++) {
    $insert_position = mt_rand(0, strlen($password));
    $password = substr($password, 0, $insert_position)
              . chr(mt_rand(128, 256 + 32) & 255)
              . substr($password, $insert_position);
  }

  return $password;
}

function decodePassword($encoded_password) {
  $password = '';

  for ($i = 0; $i < strlen($encoded_password); $i++) {
    if (ord($encoded_password[$i]) <= 32) continue;
    if (ord($encoded_password[$i]) >= 128) continue;

    $password .= $encoded_password[$i];
  }

  return $password;
}

function decryptPassword($user, $crypted_password, $fish)
{
  $ctx = hash_init('sha256');
  hash_update($ctx, $user);
  hash_update($ctx, $fish);
  $key = hash_final($ctx, true);

  $cipher = MCRYPT_RIJNDAEL_128;
  $mode = MCRYPT_MODE_ECB;

  $iv_size = mcrypt_get_iv_size($cipher, MCRYPT_MODE_ECB);
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

  $decrypted_password = mcrypt_decrypt($cipher, $key, base64_decode($crypted_password), $mode, $iv);

  return decodePassword($decrypted_password);
}

function cryptPassword($user, $password, $fish)
{
  $ctx = hash_init('sha256');
  hash_update($ctx, $user);
  hash_update($ctx, $fish);
  $key = hash_final($ctx, true);

  $encoded_password = encodePassword($password);

  $cipher = MCRYPT_RIJNDAEL_128;
  $mode = MCRYPT_MODE_ECB;

  $iv_size = mcrypt_get_iv_size($cipher, $mode);
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

  $crypted_password = mcrypt_encrypt($cipher, $key, $encoded_password, $mode, $iv);

  return base64_encode($crypted_password);
}
?>
