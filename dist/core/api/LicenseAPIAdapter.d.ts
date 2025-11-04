import type { ILicenseAPI, ILicenseLicenseData } from '../interfaces';
/** AJAX adapter implementing ILicenseAPI */
export declare class LicenseAPIAdapter implements ILicenseAPI {
    private productSlug;
    private ajaxUrl;
    private nonce;
    constructor(productSlug: string, ajaxUrl: string, nonce: string);
    /** Activate license with given key */
    activate(key: string): Promise<ILicenseLicenseData>;
    /** Deactivate current license */
    deactivate(): Promise<void>;
    /** Refresh license status */
    check(): Promise<ILicenseLicenseData | null>;
}
//# sourceMappingURL=LicenseAPIAdapter.d.ts.map