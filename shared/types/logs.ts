export default interface getLogsResponseLog {
    created_at: string,
    data: Object,
    message: string,
    LogToUser: { name: string, email: string },
    LogToAudit: { name: string } | null
}

export default interface getLogsResponse {
    logs: getLogsResponseLog[];
    logs_aggregate: {
      aggregate: {
        count: number
      }
    }
}