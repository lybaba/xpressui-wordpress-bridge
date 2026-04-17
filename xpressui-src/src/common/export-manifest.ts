import { CUSTOM_SECTION } from './Constants';
import { getWordPressIntegrationEndpoint, resolveExportSubmissionEndpoint, WORDPRESS_REST_SUBMIT_ROUTE } from './export-runtime';

export type TCreateExportManifestOptions = {
  schemaVersion: string;
  exportId: string;
  projectSchemaVersion: string;
  projectStatus: string;
  projectId: string;
  projectSlug: string;
  projectName: string;
  generatedAt: string;
  xpressuiVersion: string;
  xpressuiTarget: string;
  runtimeVersion: string;
  runtimeArtifactPath: string;
  htmlArtifactPath: string;
  configArtifactPath: string;
  projectArtifactPath: string;
  assetsDirPath: string;
  reactSnippetPath: string;
  staticHtmlSnippetPath: string;
  assetCount: number;
  checksums?: Record<string, string>;
  config: {
    sections: Record<string, Array<{ name?: string; type?: string }>>;
    workflowConfig?: {
      submissionMode?: string;
      providerMode?: string;
      resumeSupport?: string;
      documentHandling?: string;
      submissionEndpoint?: string | null;
    };
  };
};

export function createExportManifest(options: TCreateExportManifestOptions) {
  const customSections = options.config.sections[CUSTOM_SECTION] ?? [];
  const stepCount = customSections.length;
  const fieldCount = customSections.reduce((total, section) => {
    const sectionFields = options.config.sections[section.name ?? ''] ?? [];
    return total + sectionFields.length;
  }, 0);
  const workflowConfig = options.config.workflowConfig;
  const wordpressEndpoint = getWordPressIntegrationEndpoint();

  return {
    schemaVersion: options.schemaVersion,
    exportId: options.exportId,
    projectId: options.projectId,
    projectSlug: options.projectSlug,
    projectName: options.projectName,
    generatedAt: options.generatedAt,
    xpressui: {
      version: options.xpressuiVersion,
      target: options.xpressuiTarget,
    },
    artifacts: {
      html: options.htmlArtifactPath,
      config: options.configArtifactPath,
      project: options.projectArtifactPath,
      assetsDir: options.assetsDirPath,
      snippets: {
        react: options.reactSnippetPath,
        staticHtml: options.staticHtmlSnippetPath,
      },
      wordpress: {
        runtime: options.runtimeArtifactPath,
      },
    },
    checksums: {
      algorithm: 'fnv1a-64' as const,
      files: options.checksums ?? {},
    },
    workflow: {
      submissionMode: workflowConfig?.submissionMode ?? (fieldCount > 0 && customSections.length > 1 ? 'multi-step-submit' : 'direct-submit'),
      providerMode: workflowConfig?.providerMode ?? 'none',
      resumeSupport: workflowConfig?.resumeSupport ?? 'disabled',
      documentHandling: workflowConfig?.documentHandling ?? 'none',
      submissionEndpoint: resolveExportSubmissionEndpoint({
        providerMode: workflowConfig?.providerMode,
        submissionEndpoint: workflowConfig?.submissionEndpoint,
      }),
    },
    integrationTargets: {
      wordpress: {
        bridgeMode: 'shared-site-plugin' as const,
        endpoint: wordpressEndpoint,
        method: 'POST' as const,
        uploadStrategy: 'wordpress-media' as const,
        visualAssetStrategy: 'external-public-assets' as const,
        runtimeArtifact: options.runtimeArtifactPath,
        runtimeVersion: options.runtimeVersion,
        expectedRoute: WORDPRESS_REST_SUBMIT_ROUTE,
        projectIdentityFields: ['projectId', 'projectSlug', 'submissionId'] as const,
      },
    },
    meta: {
      contractVersion: 1 as const,
      projectSchemaVersion: options.projectSchemaVersion,
      projectStatus: options.projectStatus,
      assetCount: options.assetCount,
      stepCount,
      fieldCount,
    },
  };
}
