export const WORDPRESS_REST_SUBMIT_ROUTE = 'xpressui/v1/submit';
export const WORDPRESS_REST_SUBMIT_ENDPOINT_PLACEHOLDER = '__XPRESSUI_WORDPRESS_REST_URL__';

export function getDefaultWordPressSubmitEndpoints(): string[] {
  return [
    `/wp-json/${WORDPRESS_REST_SUBMIT_ROUTE}`,
    `/?rest_route=/${WORDPRESS_REST_SUBMIT_ROUTE}`,
  ];
}

export function isWordPressBridgeProviderMode(providerMode?: string | null): boolean {
  return providerMode === 'wordpress-bridge';
}

export function isDefaultWordPressSubmitEndpoint(endpoint?: string | null): boolean {
  if (!endpoint) {
    return true;
  }

  return [
    ...getDefaultWordPressSubmitEndpoints(),
    WORDPRESS_REST_SUBMIT_ENDPOINT_PLACEHOLDER,
  ].includes(endpoint);
}

export function getWordPressIntegrationEndpoint(): string {
  return `rest_url('${WORDPRESS_REST_SUBMIT_ROUTE}')`;
}

export function resolveExportSubmissionEndpoint(options: {
  providerMode?: string | null;
  submissionEndpoint?: string | null;
}): string | null {
  return isWordPressBridgeProviderMode(options.providerMode)
    ? getWordPressIntegrationEndpoint()
    : options.submissionEndpoint ?? null;
}

export function resolveHydrationSubmissionEndpoint(options: {
  providerMode?: string | null;
  submissionEndpoint?: string | null;
}): string {
  return isWordPressBridgeProviderMode(options.providerMode)
    ? WORDPRESS_REST_SUBMIT_ENDPOINT_PLACEHOLDER
    : isDefaultWordPressSubmitEndpoint(options.submissionEndpoint)
      ? WORDPRESS_REST_SUBMIT_ENDPOINT_PLACEHOLDER
      : (options.submissionEndpoint ?? WORDPRESS_REST_SUBMIT_ENDPOINT_PLACEHOLDER);
}
