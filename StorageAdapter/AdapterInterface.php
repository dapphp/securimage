<?php

namespace Securimage\StorageAdapter;

interface AdapterInterface
{
    /**
     * Store a captcha
     *
     * @return bool true if saved, false otherwise
     */
    public function store($captchaId, $captchaInfo);

    /**
     * Store captcha audio data to the adapter
     *
     * @param string $captchaId  The captcha ID to save audio data for
     * @param string $audioData  The binary audio data tos save
     * @return bool  true if data saved, false otherwise
     */
    public function storeAudioData($captchaId, $audioData);

    /**
     * Fetch a captcha and data
     *
     * @param string $captchaId The captcha ID to fetch info for
     * @param int    $what      What info to retrieve (e.g. code, image data, all)
     * @return mixed false on failure, captcha info otherwise
     */
    public function get($captchaId, $what = null);

    /**
     * Delete captcha data from the store
     *
     * @return bool true if deleted (or doesn't exist), false otherwise
     */
    public function delete($captchaId);
}
