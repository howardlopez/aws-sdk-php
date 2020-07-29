<?php
namespace Aws\Crypto;

use Aws\Exception\CryptoException;
use Aws\Kms\KmsClient;

/**
 * Uses KMS to supply materials for encrypting and decrypting data.
 */
class KmsMaterialsProviderV2 extends MaterialsProviderV2 implements MaterialsProviderInterfaceV2
{
    private $kmsClient;
    private $kmsKeyId;

    /**
     * @param KmsClient $kmsClient A KMS Client for use encrypting and
     *                             decrypting keys.
     * @param string $kmsKeyId The private KMS key id to be used for encrypting
     *                         and decrypting keys.
     */
    public function __construct(
        KmsClient $kmsClient,
        $kmsKeyId = null
    ) {
        $this->kmsClient = $kmsClient;
        $this->kmsKeyId = $kmsKeyId;
    }

    /**
     * @inheritDoc
     */
    public function fromDecryptionEnvelope(MetadataEnvelope $envelope)
    {
        if (empty($envelope[MetadataEnvelope::MATERIALS_DESCRIPTION_HEADER])) {
            throw new CryptoException('Not able to detect the materials description.');
        }

        $materialsDescription = json_decode(
            $envelope[MetadataEnvelope::MATERIALS_DESCRIPTION_HEADER],
            true
        );

        if (empty($materialsDescription['kms_cmk_id'])
            && empty($materialsDescription['aws:x-amz-cek-alg'])) {
            throw new CryptoException('Not able to detect kms_cmk_id (legacy'
                . ' implementation) or aws:x-amz-cek-alg (current implementation)'
                . ' from kms materials description.');
        }

        return new self(
            $this->kmsClient,
            isset($materialsDescription['kms_cmk_id'])
                ? $materialsDescription['kms_cmk_id']
                : $this->kmsKeyId
        );
    }

    /**
     * @inheritDoc
     */
    public function getWrapAlgorithmName()
    {
        return 'kms+context';
    }

    /**
     * @inheritDoc
     */
    public function decryptCek($encryptedCek, $materialDescription, $options)
    {
        $params = [
            'CiphertextBlob' => $encryptedCek,
            'EncryptionContext' => $materialDescription
        ];
        if (empty($options['@KmsAllowDecryptWithAnyCmk'])) {
            if (empty($this->kmsKeyId)) {
                throw new CryptoException('Decryption with KMS materials'
                    . ' provider requires the KMS key ID to be supplied, or'
                    . ' the @KmsAllowDecryptWithAnyCmk option to be set to true.');
            }
            $params['KeyId'] = $this->kmsKeyId;
        }

        $result = $this->kmsClient->decrypt($params);
        return $result['Plaintext'];
    }

    /**
     * @inheritDoc
     */
    public function generateCek($keySize, $context, $options)
    {
        $options = array_change_key_case($options);
        if (!isset($options['@kmsencryptioncontext'])
            || !is_array($options['@kmsencryptioncontext'])
        ) {
            throw new CryptoException("'@KmsEncryptionContext' is a"
                . " required argument when using KmsMaterialsProviderV2, and"
                . " must be an associative array (or empty array).");
        }
        if (isset($options['@kmsencryptioncontext']['aws:x-amz-cek-alg'])) {
            throw new CryptoException("'@KmsEncryptionContext' must not"
                . " set a value for 'aws:x-amz-cek-alg', as this is already being"
                . " used.");
        }
        $context = array_merge($options['@kmsencryptioncontext'], $context);
        $result = $this->kmsClient->generateDataKey([
            'KeyId' => $this->kmsKeyId,
            'KeySpec' => "AES_{$keySize}",
            'EncryptionContext' => $context
        ]);
        return [
            'Plaintext' => $result['Plaintext'],
            'Ciphertext' => base64_encode($result['CiphertextBlob'])
        ];
    }
}
