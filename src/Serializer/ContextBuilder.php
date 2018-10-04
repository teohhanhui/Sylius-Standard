<?php

declare(strict_types=1);

namespace App\Serializer;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Doctrine\Common\Inflector\Inflector;
use Sylius\Component\Resource\Metadata\RegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ContextBuilder implements SerializerContextBuilderInterface
{
    private $decorated;
    private $metadataRegisry;
    private $authorizationChecker;

    public function __construct(SerializerContextBuilderInterface $decorated, RegistryInterface $metadataRegisry, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->decorated = $decorated;
        $this->metadataRegisry = $metadataRegisry;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $resourceClass = $context['resource_class'] ?? null;

        try {
            $syliusMetadata = $this->metadataRegisry->getByClass($resourceClass);
        } catch (\InvalidArgumentException $e) {
            return $context;
        }

        $baseGroup = sprintf('%s_%s_%s', $syliusMetadata->getApplicationName(), $syliusMetadata->getName(), $normalization ? 'read' : 'write');

        $context['groups'][] = $baseGroup;

        if ($this->authorizationChecker->isGranted('ROLE_ADMINISTRATION_ACCESS')) {
            $context['groups'][] = $baseGroup.'_admin';
        }

        return $context;
    }
}
