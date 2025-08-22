import { event } from './event';

export const getAnalytics = () => ({
    country: event.headers?.['cloudfront-viewer-country-name'],
    state: event.headers?.['cloudfront-viewer-country-region-name'],
    city: event.headers?.['cloudfront-viewer-city'],
    zip: event.headers?.['cloudfront-viewer-postal-code'],
    ip: event.headers?.['cloudfront-viewer-address'],
    device: event.headers?.['cloudfront-is-desktop-viewer'] === 'true' ? 'desktop' :
        event.headers?.['cloudfront-is-tablet-viewer'] === 'true' ? 'tablet' :
            event.headers?.['cloudfront-is-mobile-viewer'] === 'true' ? 'mobile' : 'unknown',
    os: event.headers?.['cloudfront-is-ios-viewer'] === 'true' ? 'ios' :
        event.headers?.['cloudfront-is-ios-viewer'] === 'true' ? 'android' :
            event.headers?.['sec-ch-ua-platform']?.replaceAll('"', ''),
    landing: event?.body?.landing,
})