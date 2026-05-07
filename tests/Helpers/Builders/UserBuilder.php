<?php

namespace Tests\Helpers\Builders;

/**
 * User Builder
 * 
 * Fluent builder for creating complex user test objects
 */
class UserBuilder
{
    private array $attributes = [];

    public function __construct()
    {
        $this->attributes = [
            'userid' => 'user.' . uniqid(),
            'loginid' => 'user.' . uniqid(),
            'password' => password_hash('Test@123', PASSWORD_DEFAULT),
            'roleid' => 5,
            'rolename' => 'MOBILIZER',
            'geo_level' => 'ward',
            'geo_level_id' => 4001,
            'status' => 'ACTIVE',
            'created_date' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Create new builder instance
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Set user ID
     */
    public function withUserId(string $userid): self
    {
        $this->attributes['userid'] = $userid;
        return $this;
    }

    /**
     * Set login ID
     */
    public function withLoginId(string $loginid): self
    {
        $this->attributes['loginid'] = $loginid;
        return $this;
    }

    /**
     * Set password
     */
    public function withPassword(string $password): self
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    /**
     * Set role
     */
    public function withRole(int $roleid, string $rolename): self
    {
        $this->attributes['roleid'] = $roleid;
        $this->attributes['rolename'] = $rolename;
        return $this;
    }

    /**
     * Set as admin
     */
    public function asAdmin(): self
    {
        return $this->withRole(1, 'ADMINISTRATOR')
                    ->withGeoLevel('national', null);
    }

    /**
     * Set as mobilizer
     */
    public function asMobilizer(): self
    {
        return $this->withRole(5, 'MOBILIZER')
                    ->withGeoLevel('ward', 4001);
    }

    /**
     * Set as distributor
     */
    public function asDistributor(): self
    {
        return $this->withRole(6, 'DISTRIBUTOR')
                    ->withGeoLevel('lga', 3001);
    }

    /**
     * Set as coordinator
     */
    public function asCoordinator(): self
    {
        return $this->withRole(3, 'STATE COORDINATOR')
                    ->withGeoLevel('state', 2001);
    }

    /**
     * Set geographic level
     */
    public function withGeoLevel(string $level, ?int $levelId): self
    {
        $this->attributes['geo_level'] = $level;
        $this->attributes['geo_level_id'] = $levelId;
        return $this;
    }

    /**
     * Set status
     */
    public function withStatus(string $status): self
    {
        $this->attributes['status'] = $status;
        return $this;
    }

    /**
     * Set as active
     */
    public function active(): self
    {
        return $this->withStatus('ACTIVE');
    }

    /**
     * Set as inactive
     */
    public function inactive(): self
    {
        return $this->withStatus('INACTIVE');
    }

    /**
     * Add custom attributes
     */
    public function with(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    /**
     * Build the user array
     */
    public function build(): array
    {
        return $this->attributes;
    }

    /**
     * Build and create in database
     */
    public function create(): int
    {
        $user = $this->build();
        
        // Prepare data for insert, map 'userid' to 'loginid'
        $data = [
            'loginid' => $user['userid'],
            'username' => $user['username'] ?? null,
            'pwd' => $user['password'],
            'roleid' => $user['roleid'],
            'geo_level' => $user['geo_level'],
            'geo_level_id' => $user['geo_level_id'],
            'active' => 1,
            'is_change_password' => 0,
        ];

        $db = new \Tests\Helpers\DatabaseHelper();
        $db->insert('usr_login', $data);

        return (int) $db->getLastInsertId();
    }
}
