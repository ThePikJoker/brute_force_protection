<?php
/**
 * @author Semih Serhat Karakaya <karakayasemi@itu.edu.tr>
 * @author Michael Usher <michael.usher@aarnet.edu.au>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\BruteForceProtection\Db;

use OC\AppFramework\Utility\TimeFactory;
use OCA\BruteForceProtection\BruteForceProtectionConfig;
use OCP\IDBConnection;

/**
 * Class DBService
 * @package OCA\BruteForceProtection\Db
 */
class DbService {

	/**
	 * @var IDBConnection
	 */
	private $connection;

	/**
	 * @var TimeFactory
	 */
	private $factory;
	/**
	 * @var BruteForceProtectionConfig
	 */
	private $config;

	/**
	 * DBService constructor.
	 *
	 * @param IDBConnection $connection
	 * @param TimeFactory $factory
	 * @param BruteForceProtectionConfig $config
	 */
	public function __construct(IDBConnection $connection, TimeFactory $factory, BruteForceProtectionConfig $config) {
		$this->connection = $connection;
		$this->factory = $factory;
		$this->config = $config;
	}

	/**
	 * @param string $uid
	 */
	public function addFailedLoginAttempt($uid, $ip){
		$builder = $this->connection->getQueryBuilder();
		$builder->insert('bfp_failed_login_attempts')
			->setValue('ip', $builder->createNamedParameter($ip))
			->setValue('uid', $builder->createNamedParameter($uid))
			->setValue('attempted_at', $builder->createNamedParameter($this->factory->getTime()))
			->execute();
	}

	/**
	 * @param string $uid
	 * @param string $ip
	 *
	 * @return int
	 */
	public function getSuspiciousActivityCountForUidIpCombination($uid, $ip) {
		$builder = $this->connection->getQueryBuilder();
		$thresholdTime = (new \DateTime())->modify("-". $this->config->getBruteForceProtectionTimeThreshold() . "second")->getTimestamp();
		$attempts = $builder->selectAlias($builder->createFunction('COUNT(*)'), 'count')
			->from('bfp_failed_login_attempts')
			->where($builder->expr()->gt('attempted_at', $builder->createNamedParameter($thresholdTime)))
			->andWhere($builder->expr()->eq('uid', $builder->createNamedParameter($uid)))
			->andWhere($builder->expr()->eq('ip', $builder->createNamedParameter($ip)))
			->execute()
			->fetch();
		return intval($attempts['count']);
	}

	/**
	 * @param string $uid
	 *
	 * @return int
	 */
	public function getSuspiciousActivityCountForUid($uid) {
		$builder = $this->connection->getQueryBuilder();
		$thresholdTime = (new \DateTime())->modify("-". $this->config->getBruteForceProtectionTimeThreshold() . "second")->getTimestamp();
		$attempts = $builder->selectAlias($builder->createFunction('COUNT(*)'), 'count')
			->from('bfp_failed_login_attempts')
			->where($builder->expr()->gt('attempted_at', $builder->createNamedParameter($thresholdTime)))
			->andWhere($builder->expr()->eq('uid', $builder->createNamedParameter($uid)))
			->execute()
			->fetch();
		return intval($attempts['count']);
	}

	/**
	 * @param string $ip
	 *
	 * @return int
	 */
	public function getSuspiciousActivityCountForIp($ip) {
		$builder = $this->connection->getQueryBuilder();
		$thresholdTime = (new \DateTime())->modify("-". $this->config->getBruteForceProtectionTimeThreshold() . "second")->getTimestamp();
		$attempts = $builder->selectAlias($builder->createFunction('COUNT(*)'), 'count')
			->from('bfp_failed_login_attempts')
			->where($builder->expr()->gt('attempted_at', $builder->createNamedParameter($thresholdTime)))
			->andWhere($builder->expr()->eq('ip', $builder->createNamedParameter($ip)))
			->execute()
			->fetch();
		return intval($attempts['count']);
	}

	/**
	 * @param string $ip
	 * @return int
	 */
	public function getLastFailedLoginAttemptTimeForIp($ip) {
		$builder = $this->connection->getQueryBuilder();
		$thresholdTime = (new \DateTime())->modify("-". $this->config->getBruteForceProtectionTimeThreshold() . "second")->getTimestamp();
		$lastAttempt = $builder->select('attempted_at')
			->from('bfp_failed_login_attempts')
			->where($builder->expr()->gt('attempted_at', $builder->createNamedParameter($thresholdTime)))
			->andWhere($builder->expr()->eq('ip', $builder->createNamedParameter($ip)))
			->orderBy('attempted_at','DESC')
			->setMaxResults(1)
			->execute()
			->fetch();
		return intval($lastAttempt['attempted_at']);
	}

	/**
	 * @param string $ip
	 */
	public function deleteSuspiciousAttemptsForIp($ip) {
		$builder = $this->connection->getQueryBuilder();
		$builder->delete('bfp_failed_login_attempts')
			->where($builder->expr()->eq('ip',$builder->createNamedParameter($ip)))
			->execute();
	}

	/**
	 * @param string $uid
	 * @param string $ip
	 */
	public function deleteSuspiciousAttemptsForUidIpCombination($uid, $ip) {
		$builder = $this->connection->getQueryBuilder();
		$builder->delete('bfp_failed_login_attempts')
			->where($builder->expr()->eq('uid',$builder->createNamedParameter($uid)))
			->andWhere($builder->expr()->eq('ip', $builder->createNamedParameter($ip)))
			->execute();
	}
}
