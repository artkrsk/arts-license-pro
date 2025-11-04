import type { ILicenseConfig } from './ILicenseConfig';
import type { ILicenseLicenseData } from './ILicenseLicenseData';
/** License form component props */
export interface ILicenseFormProps {
    onActivate: (key: string) => Promise<void>;
    onDeactivate: () => Promise<void>;
    onRefresh: () => Promise<void>;
    isLoading: boolean;
    licenseKey: string | null;
    isActivated: boolean;
    license: ILicenseLicenseData | null;
    apiError: string | null;
    licenseConfig: ILicenseConfig;
    shouldRefreshPageOnLicenseChange?: boolean;
    shouldAutoRefreshLicenseOnMount?: boolean;
}
//# sourceMappingURL=ILicenseFormProps.d.ts.map