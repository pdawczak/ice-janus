<?php

namespace Ice\ExternalUserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\SerializedName,
    JMS\Serializer\Annotation\Expose,
    JMS\Serializer\Annotation\ExclusionPolicy;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ice\ExternalUserBundle\Entity\Username
 *
 * @ORM\Table(name="username")
 * @ORM\Entity(repositoryClass="Ice\ExternalUserBundle\Entity\UsernameRepository")
 * @ORM\HasLifecycleCallbacks()
 *
 * @ExclusionPolicy("ALL")
 */
class Username
{
    /**
     * @var string $initials
     *
     * @ORM\Id()
     * @ORM\Column(name="initials", type="string", length=10)
     *
     * @Assert\NotBlank(message="Initials cannot be blank")
     * @Assert\Regex(pattern="/^\p{L}+$/u", message="Initials can only contain letters")
     */
    private $initials;

    /**
     * @var integer $sequence
     *
     * @ORM\Id()
     * @ORM\Column(name="sequence", type="integer")
     *
     * @Assert\Range(min="1")
     */
    private $sequence;

    /**
     * @var string
     *
     * @ORM\Column(name="generated_username", type="string", unique=true)
     *
     * @Expose()
     * @SerializedName("username")
     *
     * @Assert\Regex(pattern="/^\p{L}+[0-9]+$/u")
     */
    private $generatedUsername;

    /**
     * @var \DateTime $generatedAt
     *
     * @ORM\Column(name="generated_at", type="datetime")
     *
     * @Expose
     */
    private $generatedAt;

    /**
     * @var string
     *
     * A printf formatted string with a single %s placeholder
     */
    private $usernameFormat;

    public function __construct($username_format)
    {
        $this->usernameFormat = $username_format;
    }

    /**
     * Set initials
     *
     * @param string $initials
     * @return Username
     */
    public function setInitials($initials)
    {
        $this->initials = $initials;
    
        return $this;
    }

    /**
     * Get initials
     *
     * @return string 
     */
    public function getInitials()
    {
        return $this->initials;
    }

    /**
     * Set sequence
     *
     * @param integer $sequence
     * @return Username
     */
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;
    
        return $this;
    }

    /**
     * Get sequence
     *
     * @return integer 
     */
    public function getSequence()
    {
        return $this->sequence;
    }
    /**
     * Set generated username. Only called by Doctrine lifecycle callback
     *
     * @param string $generated_username
     * @return Username
     */
    private function setGeneratedUsername($generated_username)
    {
        $this->generatedUsername = $generated_username;

        return $this;
    }

    /**
     * Get generated username
     *
     * @return string
     */
    public function getGeneratedUsername()
    {
        return $this->generatedUsername;
    }

    /**
     * @param \DateTime $generated_at
     * @return Username
     */
    public function setGeneratedAt(\DateTime $generated_at)
    {
        $this->generatedAt = $generated_at;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getGeneratedAt()
    {
        return $this->generatedAt;
    }

    /**
     * Before the entity is persisted:
     * * Sets the value of generateAt to the current date and time
     * * Sets generatedUsername to initial and sequence
     *
     * @ORM\PrePersist
     */
    public function doPrePersistLifecycleCallbacks()
    {
        $this->setGeneratedAt(new \DateTime());

        $username = sprintf("%s%s", $this->getInitials(), $this->getSequence());
        $generatedUsername = sprintf($this->usernameFormat, $username);
        $this->setGeneratedUsername($generatedUsername);
    }
}
