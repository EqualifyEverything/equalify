import { useState, useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useDebouncedCallback } from "use-debounce";
import * as API from "aws-amplify/api";
import { useGlobalStore } from "../utils";
import { ORGANIZATION_NAME_KEY, ssoAccountLabel, useOrganizationName } from "../queries";
import { StyledLabeledInput } from "./StyledLabeledInput";
import style from "./LoginSettingsInput.module.scss";

const apiClient = API.generateClient();

export const LoginSettingsInput = () => {
    const queryClient = useQueryClient();
    const { setAnnounceMessage } = useGlobalStore();
    const { data: savedName } = useOrganizationName();
    const [organizationName, setOrganizationName] = useState("");

    useEffect(() => {
        if (savedName !== undefined) setOrganizationName(savedName);
    }, [savedName]);

    const saveMutation = useMutation({
        mutationFn: async (value: string) => {
            await apiClient.graphql({
                query: `mutation($objects: [options_insert_input!]!) {
                    insert_options(
                        objects: $objects,
                        on_conflict: { constraint: options_pkey, update_columns: [value] }
                    ) {
                        affected_rows
                    }
                }`,
                variables: { objects: [{ key: ORGANIZATION_NAME_KEY, value }] },
            });
        },
    });

    const debouncedSave = useDebouncedCallback(
        (value: string) => {
            saveMutation.mutate(value, {
                onSuccess: () => {
                    queryClient.invalidateQueries({ queryKey: ["organizationName"] });
                    setAnnounceMessage("Login settings saved.", "success");
                },
                onError: () => {
                    setAnnounceMessage("Failed to save login settings.", "error");
                },
            });
        },
        750
    );

    const handleChange = (value: string) => {
        setOrganizationName(value);
        debouncedSave(value.trim());
    };

    return (
        <div className={style.LoginSettingsInput}>
            <h2>Login</h2>
            <hr/>
            <StyledLabeledInput>
                <label htmlFor="sso-organization-name">Organization Name</label>
                <input
                    id="sso-organization-name"
                    type="text"
                    placeholder="e.g. UIC"
                    value={organizationName}
                    onChange={(e) => handleChange(e.target.value)}
                    aria-describedby="sso-organization-name-preview"
                />
            </StyledLabeledInput>
            <p id="sso-organization-name-preview" className={style.preview}>
                The login button will read: <strong>Sign in with {ssoAccountLabel(organizationName)}</strong>
            </p>
        </div>
    );
};
