<?php /** @noinspection SqlResolve */

namespace Traits;

use \PDO;

trait HasSettings
{

    /**
     * Gets the settings data for this site
     */
    public function getSettings() {
        $stmt = $this->dbh->prepare("SELECT settings FROM settings WHERE has_settings_type = :type AND has_settings_id = :id");
        $stmt->bindValue(':type', self::SETTINGS_KEY);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();

        $settings = $stmt->fetchColumn();
        return json_decode($settings);
    }

    /**
     * @param $settings
     * @return false|int
     */
    public function saveSettings($settings) {
        $sql = <<<SQL
INSERT INTO settings SET
    settings = :settings,
    has_settings_type = :type,
    has_settings_id = :id
ON DUPLICATE KEY UPDATE
     settings = VALUES(settings),
     updated_at = NOW()
SQL;

        $encodedSettings = json_encode($settings);

        $stmt = $this->dbh->prepare($sql);
        $stmt->bindValue(':type', self::SETTINGS_KEY);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':settings', $encodedSettings);

        return $stmt->execute();
    }
}