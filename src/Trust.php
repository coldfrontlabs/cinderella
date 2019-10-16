<?php

namespace Cinderella;

class Trust {
  private $certs = [];

  public function __construct($config) {
    if (isset($config['certs'])) {
      foreach (glob($config['certs'] . '/*.pem') as $cert) {
        if ($x509 = openssl_x509_read('file://' . $cert)) {
          $this->certs[openssl_x509_fingerprint($x509, 'sha256')] = $x509;
        }
      }
    }
  }

  public function validate($data, $signature, $cert) {
    $cert = openssl_x509_read($cert);
    if (!$cert) {
      return FALSE;
    }
    $fingerprint = openssl_x509_fingerprint($cert, 'sha256');
    if (!isset($this->certs[$fingerprint])) {
      return FALSE;
    }

    $pkey = openssl_pkey_get_public($trust);
    return openssl_verify($data, $signature, $pkey);
  }

}