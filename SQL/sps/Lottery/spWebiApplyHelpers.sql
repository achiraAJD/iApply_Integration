USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebiApplyHelpers]    Script Date: 2/12/2021 1:15:21 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/*
+--------------------------------------------------------------------------------------------

| OBJECT: 	spWebiApplyHelpers
| PURPOSE:  Helper functions for iApply
+--------------------------------------------------------------------------------------------
| DATE			WHO      		DESCRIPTION OF CHANGE
| ----------- 	--------------- -------------------------------------------------------
| 29/04/2021	M Samohod		Created SP
|-------------------------------------------------------------------------------------------
| 15/11/2021     Achira          Adding GetLotteryLicenceAppDetails, GetLotteryAppFinanceDetails and GetLotteryWinnersListDetails Switch
| 25/11/2021     Achira          Adding GetSelectedLotteryFinancialReturn, GetSelectedLotteryWinnersList, GetSelectedLotteryApplicationDetails Switch
+--------------------------------------------------------------------------------------------*/
--grant execute on spWebiApplyHelpers to WebUser

ALTER PROCEDURE [dbo].[spWebiApplyHelpers]
@Switch varchar(64),
@Params varchar(max) = null
AS

BEGIN
	IF @Switch = 'GetFees' BEGIN
		SELECT CB_Code Code, LFF_FeeAmount Amount FROM LicenceFeeFramework
		INNER JOIN ComboBoxes ON CB_ID = LFF_CB_ID_FeeType
		WHERE CB_Code IN (select value from openjson(@Params)) AND LFF_DateTo IS NULL
	END

	IF @Switch = 'GetApplicationDetails' BEGIN
		IF ISNUMERIC(@Params) = 0
			SELECT @Params = JSON_VALUE(LSA_JSON, '$.info.APP_ApplicNumber') FROM LicenceSystemApplications WHERE JSON_VALUE(LSA_JSON, '$.info.LGO_Reference') = @Params
		SELECT
			APP_ID,
			APP_ApplicNumber,
			JSON_VALUE(LSA_JSON, '$.info.LGO_Reference') CustomerReferenceNumber,
			FORMAT (APP_ReceiptDate, 'dd/MM/yyyy') as APP_ReceiptDate, APP_ContactEmail,
			AT_Prefix, AT_Desc, AT_ShortDesc,
			AS_Code, AS_Desc, AS_Act,
			CB_Description FileStatus, CB_ShortDescription FileStatusShort,
			alloc.AU_Email AllocatedOfficerEmail,
			FORMAT (APP_HearingDate, 'dd/MM/yyyy') APP_HearingDate, cast(APP_HearingTime as time) APP_HearingTime,
			deleg.AU_Email DelegateEmail,
			LIC_ID, LN_LicenceNumber, PN_Name,
			(select top 1 AOR_OrdNo from ApplicationOrders where AOR_APP_ID = APP_ID order by 1 desc) AOR_OrdNo,
			(select top 1 AOR_ResultDesc from ApplicationOrders where AOR_APP_ID = APP_ID order by 1 desc) AOR_ResultDesc,
			(select top 1 FORMAT (AOR_EffectiveDate, 'dd/MM/yyyy') from ApplicationOrders where AOR_APP_ID = APP_ID order by 1 desc) AOR_EffectiveDate
		FROM Applications
		INNER JOIN ApplicationStreamTypes on APP_AST_ID = AST_ID
		INNER JOIN ApplicationStreams on AST_AS_ID = AS_ID
		INNER JOIN ApplicationTypes on AST_AT_ID = AT_ID
		INNER JOIN vwLicenceAll ON APP_LIC_ID = LIC_ID
		INNER JOIN LicenceSystemApplications on APP_ID = LSA_APP_ID
		LEFT JOIN ComboBoxes ON APP_CB_ID_FileStatus = CB_ID
		LEFT JOIN FileAllocation ON APP_ID = FA_OBJ_ID and FA_OBJT_ID = 1
		LEFT JOIN AppUsers alloc ON FA_AU_ID_AllocTo = alloc.AU_ID
		LEFT JOIN AppUsers deleg ON APP_Delegate_AU_ID = deleg.AU_ID
		WHERE APP_ApplicNumber = @Params
	END

	IF @Switch = 'GetLicenceDetails' BEGIN
		IF JSON_VALUE(@Params, '$.AS_ID') = 1 BEGIN
			SELECT
				LSL_JSON
			FROM vwLicenceSystemLicencesCurrentFast
			WHERE LSL_LIC_ID = JSON_VALUE(@Params, '$.LIC_ID')
		END ELSE IF JSON_VALUE(@Params, '$.AS_ID') = 2 BEGIN
			SELECT
				LSL_JSON
			FROM vwLicenceSystemLicencesGamingCurrentFast
			WHERE LSL_LIC_ID = JSON_VALUE(@Params, '$.LIC_ID')
		END
	END

	IF @Switch = 'GetLotteryLicenceAppDetails' BEGIN
		
		SELECT
			LLIC_LicenceNumber,
			LAPP_AST_ID,
			LAPP_LLIC_ID,
			LAPP_LC_ID,
			LAPP_ID,
			LAPP_ApplicNumber,
			LAPP_StatementPeriodFrom,
			LAPP_StatementPeriodTo,
			FORMAT(LAPP_StartDate, 'yyyy-MM-dd hh:mm:ss') AS 'LAPP_StartDate',
			FORMAT(LAPP_CloseDate, 'yyyy-MM-dd hh:mm:ss') AS 'LAPP_CloseDate',
			FORMAT(LAPP_ExpiryDate, 'yyyy-MM-dd hh:mm:ss') AS 'LAPP_ExpiryDate',
			LAPP_PromotionTitle,
			LAPP_NoTickets,
			FORMAT(LAPP_DrawDate, 'yyyy-MM-dd hh:mm:ss') AS 'LAPP_DrawDate',
			LAPP_NationalPrizeValue,
			LAPP_StatePrizeValue,
			(SELECT COUNT(*) FROM FinancialStatements WHERE la.LAPP_ID = FS_LAPP_ID AND FS_DateReceived IS NULL) OutstandingFS,
			(SELECT COUNT(*) FROM FinancialStatements WHERE la.LAPP_ID = FS_LAPP_ID AND FS_DateReceived IS NOT NULL) ReceivedFS,
			(SELECT COUNT(*) FROM WinnersList WHERE la.LAPP_ID = WL_LAPP_ID AND WL_ReceivedDate IS NULL) OutstandingWL,
			(SELECT COUNT(*) FROM WinnersList WHERE la.LAPP_ID = WL_LAPP_ID AND WL_ReceivedDate IS NOT NULL) ReceivedWL
		FROM LotteryLicences ll
		INNER JOIN LotteryApplications la ON la.LAPP_LLIC_ID = ll.LLIC_ID
		WHERE ll.LLIC_LicenceNumber = @Params		
	END
	
	IF @Switch = 'GetLotteryAppFinanceDetails' BEGIN
		SELECT 
		FS_ID,
		FS_LAPP_ID,
		FS_ReturnNumber,
		FORMAT(FS_DateReceived, 'yyyy-MM-dd hh:mm:ss') AS 'FS_DateReceived',
		FORMAT(FS_DateDue, 'yyyy-MM-dd hh:mm:ss') AS 'FS_DateDue',
		FS_GrossProceeds,
		FS_NettProceeds,
		FS_Notes,
		FS_CB_ID_ReturnType,
		FS_AmountDistributed,
		FORMAT(FS_StatementPeriodFrom, 'yyyy-MM-dd hh:mm:ss') AS 'FS_StatementPeriodFrom',
		FORMAT(FS_StatementPeriodTo, 'yyyy-MM-dd hh:mm:ss') AS 'FS_StatementPeriodTo'
		FROM LotteryApplications la 
		INNER JOIN FinancialStatements fs ON fs.FS_LAPP_ID = la.LAPP_ID
		WHERE FS_LAPP_ID = @Params AND FS_DateReceived IS NULL 
		ORDER BY FS_DateDue
	END	

	IF @Switch = 'GetLotteryWinnersListDetails' BEGIN
		SELECT 
			WL_ID,
			WL_LAPP_ID,
			FORMAT(WL_DueDate, 'yyyy-MM-dd hh:mm:ss') AS 'WL_DueDate' 
		FROM WinnersList
		WHERE WL_LAPP_ID = @Params AND WL_ReceivedDate IS NULL 
		ORDER BY WL_DueDate 
		
	END

	IF @Switch = 'GetSelectedLotteryFinancialReturn' BEGIN
		SELECT 
			FS_ID,
			FS_LAPP_ID,
			FS_ReturnNumber,
			FORMAT(FS_DateDue, 'yyyy-MM-dd hh:mm:ss') AS 'FS_DateDue',
			FORMAT(FS_StatementPeriodFrom, 'yyyy-MM-dd hh:mm:ss') AS 'FS_StatementPeriodFrom',
			FORMAT(FS_StatementPeriodTo , 'yyyy-MM-dd hh:mm:ss') AS 'FS_StatementPeriodTo '
		FROM FinancialStatements WHERE FS_ID = @Params 
	END

	IF @Switch = 'GetSelectedLotteryWinnersList' BEGIN
		SELECT 
			WL_ID,
			FORMAT(WL_DueDate, 'yyyy-MM-dd hh:mm:ss') AS 'WL_DueDate'
		FROM WinnersList WHERE WL_ID = @Params
	END
	
	IF @Switch = 'GetSelectedLotteryApplicationDetails' BEGIN

		DECLARE
			@LLIC_LicenceNumber VARCHAR(32) = NULL,
			@ED_ClientName VARCHAR(100) = NULL 
		
		SELECT @LLIC_LicenceNumber = LLIC_LicenceNumber FROM LotteryApplications
		INNER JOIN LotteryLicences ON LAPP_LLIC_ID = LLIC_ID
		WHERE LAPP_ID = @Params

		EXEC spWebGetLotteryDetails @Switch = 'GetEDClientName', @Params = @LLIC_LicenceNumber, @ED_ClientName = @ED_ClientName OUTPUT

		SELECT 
			@LLIC_LicenceNumber AS 'LLIC_LicenceNumber',
			@ED_ClientName AS 'ED_ClientName',
			LAPP_LLIC_ID,
			LAPP_LC_ID,
			LAPP_ID,
			LAPP_ApplicNumber,
			LAPP_StatementPeriodFrom,
			LAPP_StatementPeriodTo,
			FORMAT(LAPP_StartDate, 'yyyy-MM-dd hh:mm:ss') AS 'LAPP_StartDate',
			FORMAT(LAPP_CloseDate, 'yyyy-MM-dd hh:mm:ss') AS 'LAPP_CloseDate',
			FORMAT(LAPP_ExpiryDate, 'yyyy-MM-dd hh:mm:ss') AS 'LAPP_ExpiryDate',
			LAPP_PromotionTitle
		FROM LotteryApplications WHERE LAPP_ID = @Params
	END
END
