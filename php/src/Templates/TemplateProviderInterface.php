<?php

/**
 * Interface for template providers
 */
interface TemplateProviderInterface
{
    /**
     * Get all templates from this provider
     */
    public function getTemplates(): array;
    
    /**
     * Get category name
     */
    public function getCategory(): string;
    
    /**
     * Get template names
     */
    public function getTemplateNames(): array;
}