select top 10 *  from WinnersList order by WL_ID desc
SELECT 
			WL_ID,
			WL_LAPP_ID,
			FORMAT(WL_DueDate, 'yyyy-MM-dd hh:mm:ss') AS 'WL_DueDate' 
		FROM WinnersList
		WHERE WL_LAPP_ID = 44055 AND WL_ReceivedDate IS NULL 
		ORDER BY WL_DueDate 

exec spWebiApplyHelpers @Switch = 'GetSelectedLotteryWinnersList', @Params = 44059