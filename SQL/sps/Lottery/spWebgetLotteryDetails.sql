USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebGetLotteryDetails]    Script Date: 3/06/2022 2:39:21 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		Achira Warnakulasuriya
-- Create date: 24/11/2021
-- 2/12/2021 - get ED_CLientName based on Lottery Licence Number 
-- 4/12/2021 - Add UpdateFs and UpdateWl switch by Achira
-- =============================================
ALTER PROCEDURE [dbo].[spWebGetLotteryDetails]
	@Switch VARCHAR(64),
	@FS_DateReceived DATETIME = NULL,
	@FS_GrossProceeds NUMERIC(18,0) = NULL,
	@FS_NettProceeds NUMERIC(18,0) = NULL,
	@FS_AmountDistributed NUMERIC(18,0) = NULL,
	@FS_Notes VARCHAR(2000) = NULL,
	@FS_UpdatedOnline BIT = NULL,
	@FS_ID NUMERIC(18,0) = NULL,
	@WL_LAPP_ID NUMERIC(18,0) = NULL,
	@WL_ReceivedDate DATETIME = NULL, 
	@Params VARCHAR(max) = null,
	@ED_ClientName VARCHAR(max) = NULL OUTPUT,
	@Display BIT = NULL
	
	AS
BEGIN
	SET NOCOUNT ON
	DECLARE
	@LAPP_LC_ID NUMERIC(18,0),
	@LC_Code    VARCHAR(50),
	@ENT_ID NUMERIC(18,0)

	IF @Switch = 'GetEDClientName' 	BEGIN
		SELECT @LAPP_LC_ID = LAPP_LC_ID FROM LotteryLicences ll
		INNER JOIN LotteryApplications la ON la.LAPP_LLIC_ID = ll.LLIC_ID
		WHERE LLIC_LicenceNumber = @Params

		SELECT @LC_CODE = LC_Code FROM LicenceClasses WHERE LC_ID = @LAPP_LC_ID
    
		IF @LC_Code = 'MAJOR' OR @LC_Code = 'TPIP' BEGIN
			SELECT @ENT_ID = ISNULL(ENT_ENT_ID_Parent, ENT_ID) FROM LotteryLicences ll
			INNER JOIN LotteryApplications la ON ll.LLIC_ID = la.LAPP_LLIC_ID
			INNER JOIN ApplicationEntities ap ON ap.APE_LAPP_ID = la.LAPP_ID
			INNER JOIN Entities e ON e.ENT_ID = ap.APE_ENT_ID
			WHERE LLIC_LicenceNumber = @Params

			SELECT @ED_ClientName = ED_ClientName FROM Entities e
			INNER JOIN EntityDetails ed ON e.ENT_ED_ID = ed.ED_ID and e.ENT_ID = ed.ED_ENT_ID
			WHERE ENT_ID = @ENT_ID
		END

		IF @LC_Code = 'INST' OR @LC_Code = 'BINGO' BEGIN
			SELECT @ENT_ID = ISNULL(ENT_ENT_ID_Parent, ENT_ID) FROM LotteryLicences ll
			INNER JOIN LotteryApplications la ON la.LAPP_LLIC_ID = LLIC_ID
			INNER JOIN Entities e ON e.ENT_ID = la.LAPP_ENT_ID
			WHERE LLIC_LicenceNumber = @Params
			
			SELECT @ED_ClientName = ED_ClientName FROM Entities e
			INNER JOIN EntityDetails ed ON e.ENT_ED_ID = ed.ED_ID and e.ENT_ID = ed.ED_ENT_ID
			WHERE ENT_ID = @ENT_ID
		END

		IF @LC_Code = 'TPMA' BEGIN
			SELECT @ENT_ID = ISNULL(ENT_ENT_ID_Parent, ENT_ID) FROM LotteryLicences ll
			INNER JOIN LotteryApplications la ON ll.LLIC_ID = la.LAPP_LLIC_ID
			INNER JOIN ApplicationEntities ap ON ap.APE_LAPP_ID = la.LAPP_ID
			INNER JOIN Entities e ON e.ENT_ID = ap.APE_ENT_ID
			WHERE LLIC_LicenceNumber = @Params
			
			IF @ENT_ID IS NULL BEGIN
				SELECT @ENT_ID = ISNULL(ENT_ENT_ID_Parent, ENT_ID) FROM LotteryLicences ll
				INNER JOIN LotteryApplications la ON la.LAPP_LLIC_ID = LLIC_ID
				INNER JOIN Entities e ON e.ENT_ID = la.LAPP_ENT_ID
				WHERE LLIC_LicenceNumber = @Params			
			END

			SELECT @ED_ClientName = ED_ClientName FROM Entities e
			INNER JOIN EntityDetails ed ON e.ENT_ED_ID = ed.ED_ID and e.ENT_ID = ed.ED_ENT_ID
			WHERE ENT_ID = @ENT_ID
		END

		IF @Display = 1
			SELECT @ED_ClientName AS ED_ClientName 
	END

	IF @Switch = 'UpdateFS' BEGIN
		UPDATE FinancialStatements
			SET 
				FS_DateReceived = @FS_DateReceived,
				FS_GrossProceeds = @FS_GrossProceeds,
				FS_NettProceeds = @FS_NettProceeds,
				FS_AmountDistributed = @FS_AmountDistributed,
				FS_Notes = @FS_Notes,
				FS_UpdatedOnline = @FS_UpdatedOnline
		WHERE FS_ID = @FS_ID
		SELECT @FS_ID AS 'FS_ID'
	END

	IF @Switch = 'UpdateWL' BEGIN
		UPDATE WinnersList
			SET 
				WL_LAPP_ID = @WL_LAPP_ID,
				WL_ReceivedDate = @WL_ReceivedDate 
		WHERE WL_LAPP_ID = @WL_LAPP_ID
		SELECT @WL_LAPP_ID AS 'WL_LAPP_ID'
	END

	
END

GRANT EXECUTE ON spWebGetLotteryDetails TO WebUser
