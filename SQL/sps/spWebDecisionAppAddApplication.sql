USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebDecisionAppAddApplication]    Script Date: 1/07/2022 11:46:18 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO


/******************************************************************************
**		File: spWebDecisionAppAddApplication.sql
**		Name: spWebDecisionAppAddApplication
**		Desc: Add basic liquor applications
** 
*******************************************************************************
**		Change History
*******************************************************************************
**		Date:		Author:			Description:
**		--------	--------		-------------------------------------------
**		02-03-21	Dave Dennis		Created
*******************************************************************************/

--EXEC spWebDecisionAppAddApplication @APP_LIC_ID = 1, @AS_ID = 1, @AT_ID = 98, @APP_Applicant = 'Liquor and Gambling Commissioner';
--grant execute on spWebDecisionAppAddApplication to WEBUSER

ALTER PROCEDURE [dbo].[spWebDecisionAppAddApplication]
-- Table key fields
@APP_LIC_ID						NUMERIC(18,0),		 
@AS_ID							NUMERIC(18,0),
@AT_ID							NUMERIC(18,0),
-- Application data fields
@APP_Applicant					VARCHAR(200) = NULL,
@APP_Notes						VARCHAR(2000) = NULL,
@APP_ReceiptCode				VARCHAR(50) = NULL,
@APP_ReceiptDate				DATETIME = NULL,
@APP_ApplicFee					FLOAT = NULL,
@APP_AddressTo					VARCHAR(150) = NULL,
@APP_HearingDate				DATETIME = NULL,
@APP_HearingTime				VARCHAR(10) = NULL,
@APP_AdvertDateLast				DATETIME = NULL,
@APP_GazDateLast				DATETIME = NULL,
@APP_ObjectDateLast				VARCHAR(12) = NULL,
@APP_ContactPhone				VARCHAR(15) = NULL,
@APP_ContactFax					VARCHAR(15) = NULL,
@APP_ContactEmail				VARCHAR(255) = NULL,
@APP_ContactName				VARCHAR(100) = NULL,
@APP_ContactAddress1			VARCHAR(50) = NULL,
@APP_ContactAddress2			VARCHAR(50) = NULL,
@APP_ContactTown				VARCHAR(30) = NULL,
@APP_ContactPostcode			VARCHAR(4) = NULL,
@APP_ContactState				VARCHAR(3) = NULL,
@HearingAuthority				VARCHAR(20) = NULL,
@HearingType					VARCHAR(20) = NULL,
@APP_Exemption					BIT = 0,
@APP_RemovalPremisesAddress1	VARCHAR(50) = NULL,
@APP_RemovalPremisesAddress2	VARCHAR(50) = NULL,
@APP_RemovalPremisesTown		VARCHAR(30) = NULL,
@APP_RemovalPremisesPostcode	VARCHAR(4) = NULL,
@APP_RemovalPremisesState		VARCHAR(3) = NULL,
@APP_RemovalProposedPhone		VARCHAR(15) = NULL, 
@APP_RemovalProposedFax			VARCHAR(15) = NULL,
@APP_Incomplete					BIT = 0,
@APP_AdvInstructIssued			DATETIME = NULL,
@APP_OutstandingDocRec			DATETIME = NULL,
@APP_OutstandingDocRequested	DATETIME = NULL,
@GA_MachineQty         			INTEGER = 0,
@GA_SellBuyEntitlementQty 		INT = NULL,
@File_Allocation				BIT = 0,
@APP_GRP_ID						NUMERIC(18,0) = NULL,
@AU_Name						VARCHAR(16) = NULL,
@iApply                         BIT = 0, -- added by Achira
@APG_Delegate_ID				INT = NULL, -- added by Achira
@APP_Delegate_AU_ID				INT = NULL, -- added by Achira
@APG_ID							INT = NULL, -- added by Achira
@APP_CB_ID_FileStatus			INT	= NULL, -- added by Achira
@iApplylastApp					BIT = 1 -- added by achira

AS

-------------------------------------------------------------------------------------------
-- Perform Add
-------------------------------------------------------------------------------------------*/
BEGIN

SET NOCOUNT ON

DECLARE 
@Error                 			INTEGER,
@id_col			       			NUMERIC(18,0),
@LastUpdateUser		   			VARCHAR(50),
@APP_ApplicNumber				VARCHAR(8) = NULL,
@APP_AST_ID            			NUMERIC(18,0) = NULL,
@APP_LN_ID						NUMERIC(18,0) = NULL,
@APP_LC_ID						NUMERIC(18,0) = NULL,
@AT_Prefix						VARCHAR(5) = NULL


SELECT @LastUpdateUser = 'NIDDD'

--Assigning iApply for CreationUser Column (Achira)
IF @iApply = 1 BEGIN
		SELECT @LastUpdateUser = 'iApply'

-- Create a new application number CR290 - only if @APP_ApplicNumber is not entered                               
IF ISNULL(CONVERT(NUMERIC(18,0),@APP_ApplicNumber),0)=0
	EXEC spCreateApplicationNumber @AS_ID, @NewApplicationNumber=@APP_ApplicNumber OUTPUT

BEGIN TRANSACTION
	-- Get the application Stream Type from the application type
	SELECT @APP_AST_ID = AST_ID FROM ApplicationStreamTypes WHERE AST_AT_ID = @AT_ID AND AST_AS_ID = @AS_ID
	-- get the LN_ID
	SELECT @APP_LN_ID = LIC_LN_ID from Licences where LIC_ID = @APP_LIC_ID
	-- get the LC_ID
	SELECT @APP_LC_ID = LN_LC_ID from LicenceNumbers where LN_ID = @APP_LN_ID
	-- get AT_Prefix
	SELECT @AT_Prefix = AT_Prefix FROM ApplicationTypes WHERE AT_ID = @AT_ID

	INSERT INTO Applications
		(APP_LIC_ID, APP_ApplicNumber, APP_AST_ID, APP_ReceiptDate, APP_HearingDate, 
		APP_ReceiptCode, APP_ApplicFee, APP_PO_ID,APP_Notes, APP_ObjectDateLast, APP_AdvertDateLast,
		APP_GazDateLast, APP_HearingTime, APP_RC_ID, APP_AddressTo, APP_LC_ID, 
		APP_Applicant, APP_ContactName, APP_ContactPhone, APP_ContactFax , APP_ContactEmail,
		APP_ContactAddress1, APP_ContactAddress2, APP_ContactTown, APP_ContactPostcode, APP_ContactState, 
		APP_LN_ID, APP_LA_ID, APP_LSC_ID, APP_BAR_ID,
		APP_RemovalPremisesAddress1, APP_RemovalPremisesAddress2, APP_RemovalPremisesTown,
		APP_RemovalPremisesPostcode, APP_RemovalPremisesState,
		APP_RemovalProposedPhone, APP_RemovalProposedFax, APP_Incomplete,
		APP_HearingType, APP_HearingAuthority, LastUpdateDateTime, LastUpdateUser, CreationDateTime, CreationUser, APP_GRP_ID, APP_Exemption,
		APP_AdvInstructIssued, APP_OutstandingDocRec, APP_OutstandingDocRequested,APP_CB_ID_FileStatus)
	VALUES
		(@APP_LIC_ID, @APP_ApplicNumber, @APP_AST_ID, @APP_ReceiptDate, @APP_HearingDate,
		@APP_ReceiptCode, @APP_ApplicFee, null,@APP_Notes, @APP_ObjectDateLast, @APP_AdvertDateLast,
		@APP_GazDateLast, @APP_HearingDate + ' ' + @APP_HearingTime, null, @APP_AddressTo, @APP_LC_ID, 
		@APP_Applicant, @APP_ContactName, @APP_ContactPhone, @APP_ContactFax , @APP_ContactEmail,
		@APP_ContactAddress1, @APP_ContactAddress2, @APP_ContactTown, @APP_ContactPostcode, @APP_ContactState,
		@APP_LN_ID, null, null, null,
		@APP_RemovalPremisesAddress1, @APP_RemovalPremisesAddress2, @APP_RemovalPremisesTown,
		@APP_RemovalPremisesPostcode, @APP_RemovalPremisesState,
		@APP_RemovalProposedPhone, @APP_RemovalProposedFax, @APP_Incomplete,
		LEFT(@HearingType,1), LEFT(@HearingAuthority,1), GETDATE(),	@LastUpdateUser, GETDATE(), @LastUpdateUser, @APP_GRP_ID, @APP_Exemption,
		@APP_AdvInstructIssued, @APP_OutstandingDocRec, @APP_OutstandingDocRequested, @APP_CB_ID_FileStatus) 

	SELECT @Error = @@Error 
	IF @Error <> 0 
		GOTO Add_End
	
	SELECT @id_col = SCOPE_IDENTITY()

	-- If Gaming stream then create the gaming record
	IF @AS_ID = 2
		BEGIN
			INSERT INTO GamingApplications
						(GA_APP_ID,GA_MachineQty, GA_SellBuyEntitlementQty, LastUpdateDateTime, LastUpdateUser, CreationDateTime, CreationUser) 
				VALUES
						(@id_col, @GA_MachineQty, @GA_SellBuyEntitlementQty, GETDATE(), @LastUpdateUser, GETDATE(), @LastUpdateUser) 

			SELECT @Error = @@Error 
		END 

	-- ALLOCATE FILE edited by Achira
	IF @File_Allocation = 1 BEGIN
		DECLARE @AU_ID_AllocTo NUMERIC(18,0)
		IF @AU_Name IS NOT NULL BEGIN 
			EXEC @AU_ID_AllocTo = spWebDecisionAppReallocateFile @OBJ_ID = @id_col, @OBJT_ID = 1, @AllocTo = @AU_Name, @AllocBy = 'SYSTM', @LGO_Reference = @APP_Notes, @lastApp = @iApplylastApp,@GRP_ID = @APP_GRP_ID
		END
		ELSE BEGIN
			SELECT TOP 1 @AU_Name = AU_Name FROM AppUserGroups INNER JOIN AppGroup ON AUG_APG_ID = APG_ID INNER JOIN AppUsers ON AUG_AU_ID = AU_ID WHERE apg_id = @APG_ID ORDER BY NEWID()
			EXEC @AU_ID_AllocTo = spWebDecisionAppReallocateFile @OBJ_ID = @id_col, @OBJT_ID = 1, @AllocTo = @AU_Name, @AllocBy = 'SYSTM', @LGO_Reference = @APP_Notes, @lastApp = @iApplylastApp
		END
		
		IF @APP_Delegate_AU_ID IS NULL BEGIN
			SELECT TOP 1 @APP_Delegate_AU_ID = AU_ID FROM AppUserGroups INNER JOIN AppGroup ON AUG_APG_ID = APG_ID INNER JOIN AppUsers ON AUG_AU_ID = AU_ID  WHERE apg_id = @APG_Delegate_ID ORDER BY NEWID() 
		END
		UPDATE Applications SET APP_Delegate_AU_ID = @APP_Delegate_AU_ID WHERE APP_ID = @id_col 
	END	

END
	
-- All done (Ta-dah)
Add_End:       
 IF @Error = 0 
  BEGIN
	COMMIT TRANSACTION

	SELECT @APP_LIC_ID as LIC_ID, @APP_LN_ID as LN_ID, @APP_LC_ID as LC_ID, @id_col as APP_ID, (select APP_ApplicNumber from Applications where APP_ID = @id_col) as APP_ApplicNumber, @AU_Name as AU_Name, @APP_Delegate_AU_ID as APP_Delegate_AU_ID
  END
 ELSE
	ROLLBACK TRANSACTION
END


 

