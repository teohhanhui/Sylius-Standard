<?php

declare(strict_types=1);

namespace App\Serializer;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Doctrine\Common\Inflector\Inflector;
use Sylius\Component\Resource\Metadata\RegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ContextBuilder implements SerializerContextBuilderInterface
{
    private $decorated;
    private $metadataRegisry;
    private $resourceMetadataFactory;
    private $authorizationChecker;

    public function __construct(SerializerContextBuilderInterface $decorated, RegistryInterface $metadataRegisry, ResourceMetadataFactoryInterface $resourceMetadataFactory, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->decorated = $decorated;
        $this->metadataRegisry = $metadataRegisry;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $resourceClass = $context['resource_class'] ?? null;

        try {
            $this->metadataRegisry->getByClass($resourceClass);
        } catch (\InvalidArgumentException $e) {
            return $context;
        }

        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        } catch (ResourceClassNotFoundException $e) {
            return $context;
        }

        $baseGroup = sprintf('sylius_%s_%s', Inflector::tableize($resourceMetadata->getShortName()), $normalization ? 'read' : 'write');

        $context['groups'][] = $baseGroup;

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $context['groups'][] = $baseGroup.'_admin';
        }

        return $context;
    }
}
