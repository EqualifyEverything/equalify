import { ReactNode } from "react";
import styles from "./AuditHeader.module.scss";
import { Link, useNavigate } from "react-router-dom";
import { StyledButton } from "./StyledButton";
import { FaPen, FaTrash, FaClipboard } from "react-icons/fa";
import { GrPowerCycle } from "react-icons/gr";
import { createLog } from "#src/utils/createLog.ts";
import { useGlobalStore } from "../utils";
import * as API from "aws-amplify/api";
import { QueryClient, useQueryClient } from "@tanstack/react-query";

interface AuditHeaderProps extends React.PropsWithChildren {
  isShared: boolean;
  queryClient: QueryClient;
  audit: any;
  auditId: string | undefined;
}

export const AuditHeader = ({
  isShared,
  queryClient,
  audit,
  auditId,
}: AuditHeaderProps) => {
  const navigate = useNavigate();
  const { setAnnounceMessage } = useGlobalStore();

  const copyCurrentLocationToClipboard = async () => {
    try {
      await navigator.clipboard.writeText(
        window.location.origin +
          location.pathname.replace("/audits/", "/shared/")
      );
      console.log(
        `URL ${window.location.origin + location.pathname} copied to clipboard!`
      );
      setAnnounceMessage(
        `URL ${window.location.origin + location.pathname} copied to clipboard!`, "success"
      );
    } catch (err) {
      console.error("Failed to copy URLs: ", err);
    }
  };
  const deleteAudit = async () => {
    if (confirm(`Are you sure you want to delete this audit?`)) {
      const response = await (
        await API.post({
          apiName: "auth",
          path: "/deleteAudit",
          options: { body: { id: auditId! } },
        }).response
      ).body.json();
      //console.log(response);
      await queryClient.refetchQueries({ queryKey: ["audits"] });
      // aria & logging
      setAnnounceMessage(`Deleted audit ${audit.name}.`, "success");
      await createLog(`Deleted audit ${audit.name}.`, auditId);

      navigate("/audits");
      return;
    }
  };
  const rescanAudit = async () => {
    if (confirm(`Are you sure you want to re-scan this audit?`)) {
      const response = await (
        await API.post({
          apiName: "auth",
          path: "/rescanAudit",
          options: { body: { id: auditId! } },
        }).response
      ).body.json();
      //console.log(response);
      await queryClient.refetchQueries({ queryKey: ["audits"] });
      // aria & logging
      setAnnounceMessage(`Scanning audit ${audit.name}...`);
      return;
    }
  };

  const renameAudit = async () => {
    const newName = prompt(
      `What would you like to rename this audit to?`,
      audit?.name
    );
    if (newName) {
      const response = await (
        await API.post({
          apiName: "auth",
          path: "/updateAudit",
          options: { body: { id: auditId!, name: newName } },
        }).response
      ).body.json();
      //console.log(response);
      await queryClient.refetchQueries({ queryKey: ["audit", auditId] });
      // aria & logging
      setAnnounceMessage(`Audit ${audit.name} renamed to ${newName}`, "success");
      return;
    }
  };
  return (
    <div className={styles.AuditHeader}>
      
      <div className={styles["inner"]}>
        <div className={styles["header-l"]}>
        <h1 className="initial-focus-element">
          <span className={styles["audit-name-label"]}>Audit</span> {audit?.name}
        </h1>
        {!isShared && (
          <div className={styles["buttons-l"]}>
            <StyledButton
              onClick={renameAudit}
              label="Rename Audit"
              icon={<FaPen />}
              showLabel={false}
            />
            <StyledButton
              onClick={deleteAudit}
              label="Delete Audit"
              icon={<FaTrash />}
              showLabel={false}
            />
          </div>
        )}
        </div>

        <div className={styles["buttons-r"]}>
        {!isShared && (
            <StyledButton
              onClick={rescanAudit}
              label="Scan Now"
              icon={<GrPowerCycle />}
              variant="dark"
            />
        )}
        <StyledButton
          onClick={copyCurrentLocationToClipboard}
          label="Share"
          icon={<FaClipboard />}
        />
        </div>
      </div>
    </div>
  );
};
