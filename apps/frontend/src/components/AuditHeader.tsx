import { ReactNode, useState } from "react";
import styles from "./AuditHeader.module.scss";
import { Link, useNavigate } from "react-router-dom";
import { StyledButton } from "./StyledButton";
import { FaPen, FaTrash, FaClipboard } from "react-icons/fa";
import { GrPowerCycle } from "react-icons/gr";
import { createLog } from "#src/utils/createLog.ts";
import { useGlobalStore } from "../utils";
import * as API from "aws-amplify/api";
import { QueryClient, useQueryClient } from "@tanstack/react-query";
import { SkeletonAuditHeader } from "./Skeleton";

interface AuditHeaderProps extends React.PropsWithChildren {
  isShared: boolean;
  queryClient: QueryClient;
  audit: any;
  auditId: string | undefined;
  scans?: any[];
}

export const AuditHeader = ({
  isShared,
  queryClient,
  audit,
  auditId,
  scans,
}: AuditHeaderProps) => {
  const navigate = useNavigate();
  const { setAnnounceMessage } = useGlobalStore();
  const [isScanning, setIsScanning] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [isRenaming, setIsRenaming] = useState(false);

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
      setIsDeleting(true);
      try {
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
      } finally {
        setIsDeleting(false);
      }
      return;
    }
  };
  const rescanAudit = async () => {
    if (confirm(`Are you sure you want to re-scan this audit?`)) {
      setIsScanning(true);
      try {
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
      } finally {
        setIsScanning(false);
      }
      return;
    }
  };

  // Check if there's an active scan (not complete or failed)
  const hasActiveScan = scans && scans.length > 0 &&
    scans[scans.length - 1].status !== "complete" &&
    scans[scans.length - 1].status !== "failed";

  const renameAudit = async () => {
    const newName = prompt(
      `What would you like to rename this audit to?`,
      audit?.name
    );
    if (newName) {
      setIsRenaming(true);
      try {
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
      } finally {
        setIsRenaming(false);
      }
      return;
    }
  };

  // Show skeleton while audit is loading
  if (!audit) {
    return <SkeletonAuditHeader />;
  }

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
                loading={isRenaming}
                disabled={isRenaming || isDeleting}
              />
              <StyledButton
                onClick={deleteAudit}
                label="Delete Audit"
                icon={<FaTrash />}
                showLabel={false}
                loading={isDeleting}
                disabled={isRenaming || isDeleting}
              />
            </div>
          )}
        </div>

        <div className={styles["buttons-r"]}>
          {!isShared && (
            <StyledButton
              onClick={rescanAudit}
              label={hasActiveScan ? "Scanning..." : "Scan Now"}
              icon={<GrPowerCycle />}
              variant="dark"
              loading={isScanning || hasActiveScan}
              loadingText="Scanning..."
              disabled={hasActiveScan}
              className="audit-main-button"
            //title={hasActiveScan ? "A scan is already in progress" : undefined}
            />
          )}
          <StyledButton
            onClick={copyCurrentLocationToClipboard}
            label="Share"
            className="audit-main-button"
            icon={<FaClipboard />}
          />
        </div>
      </div>
    </div>
  );
};
