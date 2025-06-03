<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\GraphQL;

/**
 * GraphQL schema definition for ThinkPHP Rector API
 */
final class Schema
{
    public static function getSchema(): string
    {
        return '
            type Query {
                # Project queries
                projects: [Project!]!
                project(id: ID!): Project
                projectAnalysis(path: String!): ProjectAnalysis!
                
                # Upgrade queries
                upgradeHistory: [UpgradeRecord!]!
                upgradeStatus(id: ID!): UpgradeStatus
                
                # Plugin queries
                plugins: [Plugin!]!
                plugin(name: String!): Plugin
                
                # Performance queries
                performanceMetrics(projectId: ID, timeRange: TimeRange): PerformanceMetrics!
                
                # System queries
                systemHealth: SystemHealth!
                configuration: Configuration!
            }

            type Mutation {
                # Project mutations
                createProject(input: CreateProjectInput!): CreateProjectResult!
                updateProject(id: ID!, input: UpdateProjectInput!): UpdateProjectResult!
                deleteProject(id: ID!): DeleteProjectResult!
                
                # Upgrade mutations
                startUpgrade(input: StartUpgradeInput!): StartUpgradeResult!
                startBatchUpgrade(input: StartBatchUpgradeInput!): StartBatchUpgradeResult!
                cancelUpgrade(id: ID!): CancelUpgradeResult!
                
                # Plugin mutations
                installPlugin(input: InstallPluginInput!): InstallPluginResult!
                uninstallPlugin(name: String!): UninstallPluginResult!
                configurePlugin(name: String!, config: JSON!): ConfigurePluginResult!
                
                # Configuration mutations
                updateConfiguration(input: UpdateConfigurationInput!): UpdateConfigurationResult!
            }

            type Subscription {
                # Real-time upgrade progress
                upgradeProgress(upgradeId: ID!): UpgradeProgress!
                
                # System notifications
                systemNotifications: SystemNotification!
                
                # Performance monitoring
                performanceUpdates: PerformanceUpdate!
            }

            # Core Types
            type Project {
                id: ID!
                name: String!
                path: String!
                currentVersion: String
                targetVersion: String
                status: ProjectStatus!
                lastUpgrade: DateTime
                createdAt: DateTime!
                updatedAt: DateTime!
                analysis: ProjectAnalysis
                upgradeHistory: [UpgradeRecord!]!
            }

            type ProjectAnalysis {
                codeQualityScore: Float!
                complexityMetrics: ComplexityMetrics!
                securityAssessment: SecurityAssessment!
                performancePredictions: PerformancePredictions!
                upgradeRecommendations: [UpgradeRecommendation!]!
                riskAssessment: [RiskAssessment!]!
                aiInsights: [AIInsight!]!
                analyzedAt: DateTime!
            }

            type ComplexityMetrics {
                cyclomaticComplexity: Float!
                cognitiveComplexity: Float!
                maintainabilityIndex: Float!
                technicalDebt: Float!
                linesOfCode: Int!
                filesAnalyzed: Int!
            }

            type SecurityAssessment {
                overallScore: Float!
                vulnerabilities: [SecurityVulnerability!]!
                criticalIssues: Int!
                highIssues: Int!
                mediumIssues: Int!
                lowIssues: Int!
            }

            type SecurityVulnerability {
                type: VulnerabilityType!
                severity: Severity!
                description: String!
                file: String!
                line: Int
                recommendation: String!
            }

            type PerformancePredictions {
                overallImpact: ImpactLevel!
                confidence: Float!
                responseTime: PerformanceMetric!
                memoryUsage: PerformanceMetric!
                throughput: PerformanceMetric!
            }

            type PerformanceMetric {
                currentBaseline: String!
                predictedChange: String!
                confidence: Float!
            }

            type UpgradeRecommendation {
                type: RecommendationType!
                priority: Priority!
                title: String!
                description: String!
                reasoning: String!
                benefits: [String!]!
                risks: [String!]!
                effortEstimate: String
            }

            type RiskAssessment {
                type: RiskType!
                severity: Severity!
                probability: Float!
                impact: ImpactLevel!
                description: String!
                mitigation: String!
            }

            type AIInsight {
                type: InsightType!
                severity: Severity!
                title: String!
                description: String!
                recommendation: String!
                confidence: Float!
            }

            type UpgradeRecord {
                id: ID!
                projectId: ID!
                fromVersion: String!
                toVersion: String!
                status: UpgradeStatus!
                startedAt: DateTime!
                completedAt: DateTime
                duration: Int
                changes: [UpgradeChange!]!
                errors: [UpgradeError!]!
                warnings: [UpgradeWarning!]!
                performanceMetrics: UpgradePerformanceMetrics
            }

            type UpgradeChange {
                type: ChangeType!
                file: String!
                description: String!
                linesChanged: Int!
            }

            type UpgradeError {
                code: String!
                message: String!
                file: String
                line: Int
                severity: Severity!
            }

            type UpgradeWarning {
                code: String!
                message: String!
                file: String
                line: Int
                recommendation: String
            }

            type UpgradePerformanceMetrics {
                totalDuration: Int!
                filesProcessed: Int!
                transformationsApplied: Int!
                memoryUsage: String!
                cpuUsage: Float!
            }

            type Plugin {
                name: String!
                version: String!
                description: String!
                author: String!
                status: PluginStatus!
                hooks: [String!]!
                configuration: JSON
                installedAt: DateTime!
            }

            type PerformanceMetrics {
                timeRange: TimeRange!
                metrics: [MetricData!]!
                summary: PerformanceSummary!
            }

            type MetricData {
                timestamp: DateTime!
                metric: String!
                value: Float!
                unit: String!
            }

            type PerformanceSummary {
                averageUpgradeTime: Float!
                successRate: Float!
                totalUpgrades: Int!
                averageFilesPerUpgrade: Float!
            }

            type SystemHealth {
                status: HealthStatus!
                uptime: Int!
                version: String!
                checks: [HealthCheck!]!
                lastChecked: DateTime!
            }

            type HealthCheck {
                name: String!
                status: HealthStatus!
                message: String
                value: String
            }

            type Configuration {
                general: GeneralConfig!
                performance: PerformanceConfig!
                security: SecurityConfig!
                plugins: PluginConfig!
            }

            # Input Types
            input CreateProjectInput {
                name: String!
                path: String!
                targetVersion: String
                description: String
            }

            input UpdateProjectInput {
                name: String
                targetVersion: String
                description: String
            }

            input StartUpgradeInput {
                projectId: ID!
                fromVersion: String
                toVersion: String!
                options: UpgradeOptions
            }

            input StartBatchUpgradeInput {
                projects: [BatchUpgradeProject!]!
                options: BatchUpgradeOptions
            }

            input BatchUpgradeProject {
                projectId: ID!
                fromVersion: String
                toVersion: String!
                priority: Priority
            }

            input UpgradeOptions {
                createBackup: Boolean
                dryRun: Boolean
                continueOnError: Boolean
                enablePerformanceAnalysis: Boolean
                customRules: [String!]
                skipRules: [String!]
            }

            input BatchUpgradeOptions {
                maxParallelUpgrades: Int
                delayBetweenUpgrades: Int
                createBackups: Boolean
                continueOnError: Boolean
                enablePerformanceAnalysis: Boolean
            }

            input InstallPluginInput {
                packageUrl: String!
                configuration: JSON
            }

            input UpdateConfigurationInput {
                general: GeneralConfigInput
                performance: PerformanceConfigInput
                security: SecurityConfigInput
                plugins: PluginConfigInput
            }

            input TimeRange {
                start: DateTime!
                end: DateTime!
            }

            # Result Types
            type CreateProjectResult {
                success: Boolean!
                project: Project
                errors: [String!]!
            }

            type UpdateProjectResult {
                success: Boolean!
                project: Project
                errors: [String!]!
            }

            type DeleteProjectResult {
                success: Boolean!
                message: String!
            }

            type StartUpgradeResult {
                success: Boolean!
                upgradeId: ID
                message: String!
                errors: [String!]!
            }

            type StartBatchUpgradeResult {
                success: Boolean!
                batchId: ID
                message: String!
                scheduledUpgrades: [ID!]!
                errors: [String!]!
            }

            type CancelUpgradeResult {
                success: Boolean!
                message: String!
            }

            type InstallPluginResult {
                success: Boolean!
                plugin: Plugin
                message: String!
                errors: [String!]!
            }

            type UninstallPluginResult {
                success: Boolean!
                message: String!
            }

            type ConfigurePluginResult {
                success: Boolean!
                plugin: Plugin
                message: String!
                errors: [String!]!
            }

            type UpdateConfigurationResult {
                success: Boolean!
                configuration: Configuration
                message: String!
                errors: [String!]!
            }

            # Subscription Types
            type UpgradeProgress {
                upgradeId: ID!
                status: UpgradeStatus!
                progress: Float!
                currentStep: String!
                filesProcessed: Int!
                totalFiles: Int!
                estimatedTimeRemaining: Int
                errors: [UpgradeError!]!
                warnings: [UpgradeWarning!]!
            }

            type SystemNotification {
                id: ID!
                type: NotificationType!
                severity: Severity!
                title: String!
                message: String!
                timestamp: DateTime!
                data: JSON
            }

            type PerformanceUpdate {
                timestamp: DateTime!
                metrics: [MetricData!]!
            }

            # Enums
            enum ProjectStatus {
                ACTIVE
                UPGRADING
                ERROR
                ARCHIVED
            }

            enum UpgradeStatus {
                PENDING
                RUNNING
                COMPLETED
                FAILED
                CANCELLED
            }

            enum PluginStatus {
                ACTIVE
                INACTIVE
                ERROR
            }

            enum HealthStatus {
                HEALTHY
                WARNING
                CRITICAL
            }

            enum VulnerabilityType {
                SQL_INJECTION
                XSS
                CSRF
                FILE_INCLUSION
                CODE_INJECTION
                AUTHENTICATION_BYPASS
                AUTHORIZATION_FLAW
                DATA_EXPOSURE
            }

            enum Severity {
                CRITICAL
                HIGH
                MEDIUM
                LOW
                INFO
            }

            enum Priority {
                CRITICAL
                HIGH
                MEDIUM
                LOW
            }

            enum ImpactLevel {
                POSITIVE
                NEUTRAL
                NEGATIVE
            }

            enum RecommendationType {
                VERSION_RECOMMENDATION
                REFACTORING_RECOMMENDATION
                SECURITY_RECOMMENDATION
                PERFORMANCE_RECOMMENDATION
            }

            enum RiskType {
                TECHNICAL_DEBT
                COMPLEXITY
                DEPENDENCIES
                SECURITY
                PERFORMANCE
            }

            enum InsightType {
                PATTERN_INSIGHT
                COMPLEXITY_INSIGHT
                SECURITY_INSIGHT
                PERFORMANCE_INSIGHT
            }

            enum ChangeType {
                FILE_MODIFIED
                FILE_CREATED
                FILE_DELETED
                CONFIGURATION_UPDATED
                DEPENDENCY_UPDATED
            }

            enum NotificationType {
                UPGRADE_STARTED
                UPGRADE_COMPLETED
                UPGRADE_FAILED
                SYSTEM_WARNING
                SYSTEM_ERROR
                PLUGIN_INSTALLED
                PLUGIN_ERROR
            }

            # Scalars
            scalar DateTime
            scalar JSON

            # Configuration Types
            type GeneralConfig {
                defaultBackupEnabled: Boolean!
                logLevel: String!
                maxConcurrentUpgrades: Int!
                upgradeTimeout: Int!
            }

            type PerformanceConfig {
                enableMetrics: Boolean!
                metricsRetentionDays: Int!
                enableProfiling: Boolean!
                memoryLimit: String!
            }

            type SecurityConfig {
                enableVulnerabilityScanning: Boolean!
                scanDepth: String!
                quarantineThreats: Boolean!
                notifyOnCritical: Boolean!
            }

            type PluginConfig {
                autoUpdate: Boolean!
                allowThirdParty: Boolean!
                sandboxMode: Boolean!
            }

            input GeneralConfigInput {
                defaultBackupEnabled: Boolean
                logLevel: String
                maxConcurrentUpgrades: Int
                upgradeTimeout: Int
            }

            input PerformanceConfigInput {
                enableMetrics: Boolean
                metricsRetentionDays: Int
                enableProfiling: Boolean
                memoryLimit: String
            }

            input SecurityConfigInput {
                enableVulnerabilityScanning: Boolean
                scanDepth: String
                quarantineThreats: Boolean
                notifyOnCritical: Boolean
            }

            input PluginConfigInput {
                autoUpdate: Boolean
                allowThirdParty: Boolean
                sandboxMode: Boolean
            }
        ';
    }
}
