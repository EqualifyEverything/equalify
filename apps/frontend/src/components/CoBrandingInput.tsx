import { useState, useEffect } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useDebouncedCallback } from "use-debounce";
import * as Slider from "@radix-ui/react-slider";
import * as API from "aws-amplify/api";
import { useGlobalStore } from "../utils";
import { StyledLabeledInput } from "./StyledLabeledInput";
import { StyledButton } from "./StyledButton";
import style from "./CoBrandingInput.module.scss";
import * as  Label from "@radix-ui/react-label";
import { MdClose } from "react-icons/md";

const apiClient = API.generateClient();

const LOGO_SIZE_MIN = 32;
const LOGO_SIZE_MAX = 150;
const LOGO_SIZE_DEFAULT = 50;

export const CoBrandingInput = () => {
    const queryClient = useQueryClient();
    const { setAnnounceMessage } = useGlobalStore();

    const [logoUrl, setLogoUrl] = useState("");
    const [logoSize, setLogoSize] = useState(LOGO_SIZE_DEFAULT);
    const [logoAltText, setLogoAltText] = useState("");

    const normalizeUrl = (raw: string) => {
        const trimmed = raw.trim();
        if (trimmed && !/^https?:\/\//i.test(trimmed)) return "https://" + trimmed;
        return trimmed;
    };

    const urlError = (() => {
        if (!logoUrl) return null;
        try { new URL(logoUrl); return null; }
        catch { return "Please enter a valid URL (e.g. https://example.com/logo.png)."; }
    })();

    const { data } = useQuery({
        queryKey: ["cobranding"],
        queryFn: async () => {
            const response = await apiClient.graphql({
                query: `query {
                    options(where: { key: { _in: ["cobranding_logo_url", "cobranding_logo_size", "cobranding_logo_alt_text"] } }) {
                        key
                        value
                    }
                }`,
            });
            const rows: { key: string; value: string }[] = (response as any)?.data?.options ?? [];
            return Object.fromEntries(rows.map(({ key, value }) => [key, value]));
        },
    });

    useEffect(() => {
        if (data) {
            setLogoUrl(data.cobranding_logo_url ?? "");
            setLogoSize(Number(data.cobranding_logo_size) || LOGO_SIZE_DEFAULT);
            setLogoAltText(data.cobranding_logo_alt_text ?? "");
        }
    }, [data]);

    const saveMutation = useMutation({
        mutationFn: async ({ url, size, altText }: { url: string; size: number; altText: string }) => {
            await apiClient.graphql({
                query: `mutation($objects: [options_insert_input!]!) {
                    insert_options(
                        objects: $objects,
                        on_conflict: { constraint: options_pkey, update_columns: [value] }
                    ) {
                        affected_rows
                    }
                }`,
                variables: {
                    objects: [
                        { key: "cobranding_logo_url", value: url },
                        { key: "cobranding_logo_size", value: String(size) },
                        { key: "cobranding_logo_alt_text", value: altText },
                    ],
                },
            });
        },
    });

    const debouncedSave = useDebouncedCallback(
        (url: string, size: number, altText: string) => {
            saveMutation.mutate({ url, size, altText }, {
                onSuccess: () => {
                    queryClient.invalidateQueries({ queryKey: ["cobranding"] });
                    setAnnounceMessage("Co-branding settings saved.", "success");
                },
                onError: () => {
                    setAnnounceMessage("Failed to save co-branding settings.", "error");
                },
            });
        },
        750
    );

    const handleUrlChange = (raw: string) => {
        setLogoUrl(raw);
        try { new URL(raw.trim()); debouncedSave(raw.trim(), logoSize, logoAltText); } catch { /* invalid — don't save */ }
    };

    const handleUrlBlur = () => {
        const normalized = normalizeUrl(logoUrl);
        setLogoUrl(normalized);
        if (normalized) {
            try { new URL(normalized); debouncedSave(normalized, logoSize, logoAltText); } catch { /* invalid */ }
        } else {
            debouncedSave(normalized, logoSize, logoAltText);
        }
    };

    const handleSizeChange = ([val]: number[]) => {
        setLogoSize(val);
        debouncedSave(logoUrl, val, logoAltText);
    };

    const handleAltTextChange = (val: string) => {
        setLogoAltText(val);
        debouncedSave(logoUrl, logoSize, val);
    };

    return (
        <div className={style.CoBrandingInput}>
            <h2>Co-Branding</h2>
            <hr/>
            <StyledLabeledInput>
                <label htmlFor="cobranding-logo-url">Logo URL</label>
                <div className={style.urlInputRow}>
                    <input
                        id="cobranding-logo-url"
                        type="url"
                        placeholder="https://example.com/logo.png"
                        value={logoUrl}
                        onChange={(e) => handleUrlChange(e.target.value)}
                        onBlur={handleUrlBlur}
                        aria-describedby={urlError ? "cobranding-logo-url-error" : undefined}
                        aria-invalid={!!urlError}
                    />
                    {logoUrl && (
                        <StyledButton
                            variant="naked"
                            icon={<MdClose />}
                            label="Clear URL"
                            showLabel={false}
                            onClick={() => {
                                setLogoUrl("");
                                debouncedSave("", logoSize, logoAltText);
                            }}
                        />
                    )}
                </div>
                {urlError && (
                    <span id="cobranding-logo-url-error" className={style.urlError}>
                        {urlError}
                    </span>
                )}
            </StyledLabeledInput>
            <StyledLabeledInput>
                <label htmlFor="cobranding-logo-alt-text">Logo Alt Text</label>
                <input
                    id="cobranding-logo-alt-text"
                    type="text"
                    placeholder="Company logo"
                    value={logoAltText}
                    onChange={(e) => handleAltTextChange(e.target.value)}
                />
            </StyledLabeledInput>
            <StyledLabeledInput>
                <Label.Root id="cobranding-logo-size-label">Logo Size</Label.Root>
                <div className={style.sliderRow}>
                    <Slider.Root
                        className={style.sliderRoot}
                        min={LOGO_SIZE_MIN}
                        max={LOGO_SIZE_MAX}
                        step={1}
                        value={[logoSize]}
                        onValueChange={handleSizeChange}
                        aria-labelledby="cobranding-logo-size-label"
                    >
                        <Slider.Track className={style.sliderTrack}>
                            <Slider.Range className={style.sliderRange} />
                        </Slider.Track>
                        <Slider.Thumb className={style.sliderThumb} />
                    </Slider.Root>
                    <input
                        id="cobranding-logo-size"
                        type="number"
                        min={LOGO_SIZE_MIN}
                        max={LOGO_SIZE_MAX}
                        value={logoSize}
                        className={style.sizeInput}
                        aria-labelledby="cobranding-logo-size-label"
                        onChange={(e) => {
                            const val = Math.min(LOGO_SIZE_MAX, Math.max(LOGO_SIZE_MIN, Number(e.target.value)));
                            handleSizeChange([val]);
                        }}
                    />
                </div>
            </StyledLabeledInput>

        </div>
    );
};
