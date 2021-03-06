USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebTmpAddApplication]    Script Date: 1/07/2022 11:43:46 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO


/******************************************************************************
**		File: spWebTmpAddApplication.sql
**		Name: spWebTmpAddApplication
**		Desc: Add or modify applications

** RETURN VALUE: 0 - Success,
**               1 - Business rule warnings issued,
**               2 - Timestamp check failed
**               3 - Business rule error
** 
*******************************************************************************
**		Change History
*******************************************************************************
**		Date:		Author:			Description:
**		--------	--------		-------------------------------------------
**		02-02-17	Dave Dennis		Created
*******************************************************************************/


ALTER Procedure [dbo].[spWebTmpAddApplication]

@LastUpdateUser		   VARCHAR(50),
@CreateLicence         CHAR = NULL,
-- Table key fields
@APP_LIC_ID            numeric(18,0) = NULL,		 
@APP_LN_ID             numeric(18,0) = NULL,
@APP_LC_ID             numeric(18,0) = NULL,
@AS_ID                 numeric(18,0),
@AT_ID                 numeric(18,0),
@FA_AU_ID_AllocTo	   numeric(18,0) = NULL,
-- Application data fields
@APP_Applicant         VARCHAR(200) = NULL,
@APP_Notes			   VARCHAR(2000) = NULL,
@APP_ReceiptCode	   varchar(50) = NULL,
@APP_ReceiptDate       VARCHAR(12) = NULL,
@APP_ApplicFee         FLOAT = NULL,
@APP_AddressTo         VARCHAR(150) = NULL,
@APP_ApplicNumber      VARCHAR(8) = NULL,
@APP_HearingDate       VARCHAR(12) = NULL,
@APP_HearingTime       VARCHAR(10) = NULL,
@APP_AdvertDateLast    VARCHAR(12) = NULL,
@APP_GazDateLast       VARCHAR(12) = NULL,
@APP_ObjectDateLast    VARCHAR(12) = NULL,
@APP_ContactPhone      VARCHAR(15) = NULL,
@APP_ContactFax        VARCHAR(15) = NULL,
@APP_ContactEmail      VARCHAR(255) = NULL,
@APP_ContactName       VARCHAR(100) = NULL,
@APP_ContactAddress1   VARCHAR(50) = NULL,
@APP_ContactAddress2   VARCHAR(50) = NULL,
@APP_ContactTown       VARCHAR(30) = NULL,
@APP_ContactPostcode   VARCHAR(4) = NULL,
@APP_ContactState      VARCHAR(3) = NULL,
@HearingAuthority      VARCHAR(20) = NULL,
@HearingType           VARCHAR(20) = NULL,
-- Gaming application
@GA_MachineQty         INTEGER = NULL,
@GA_SellBuyEntitlementQty INT = NULL,
-- Licence data 
@LIC_PremisesAddress1  VARCHAR(50) = NULL,
@LIC_PremisesAddress2  VARCHAR(50) = NULL,
@LIC_PremisesTown      VARCHAR(30) = NULL,
@LIC_PremisesPostcode  VARCHAR(4) = NULL,
@LIC_PremisesPhone     VARCHAR(15) = NULL,
@LIC_PremisesFax       VARCHAR(15) = NULL,
@LIC_PremisesEmail     VARCHAR(255) = NULL,
@LIC_PremisesWeb       VARCHAR(255) = NULL,
@LIC_PremisesState     VARCHAR(5) = NULL,
@LIC_PN_ID             numeric(18,0) = NULL,
@LIC_InterstateJursidiction VARCHAR(50) = NULL, -- achira
@LIC_InterstateLicenceNumber VARCHAR(50) = NULL, --achira
@LIC_PremisesMobile    VARCHAR(15) = NULL, --achira
-- Premises data
@PN_Name               VARCHAR(80) = NULL,
@APP_Exemption bit = NULL,
@APP_RemovalPremisesAddress1 varchar(50) = NULL,
@APP_RemovalPremisesAddress2 varchar(50) = NULL,
@APP_RemovalPremisesTown varchar(30) = NULL,
@APP_RemovalPremisesPostcode varchar(4) = NULL,
@APP_RemovalPremisesState varchar(3) = NULL,
@APP_RemovalProposedPhone varchar(15) = NULL, 
@APP_RemovalProposedFax varchar(15) = NULL,
@APP_Incomplete bit = NULL,
@APP_AdvInstructIssued DATETIME = NULL,
@APP_OutstandingDocRec DATETIME = NULL,
@APP_OutstandingDocRequested DATETIME = NULL,
--for updating documents
@OA_ID             numeric(18,0) = NULL,
@CreateLicenceOnly  BIT = 0 -- added by achira

AS

DECLARE @Warnings              INTEGER,
        @BusinessErrors        INTEGER,
        @LN_LicenceNumber      INTEGER,
        @APP_AST_ID            INTEGER,
        @Check_Class           CHAR(3),
        @New_Class             CHAR(3),
        @Class_Change          INTEGER,
        @Premise_Update        INTEGER,
        @Temp_APP_LIC_ID       INTEGER,
        @LN_LIC_ID             INTEGER, 
        @Error                 INTEGER,
        @LS_LD_ID			   numeric(18,0),
		@APP_LC_Desc		   VARCHAR(255), -- achira
		@id_col			       numeric(18,0)

SELECT @Warnings = 0
SELECT @BusinessErrors = 0
SELECT @Class_Change = 0
SELECT @Premise_Update = 0


-------------------------------------------------------------------------------------------
-- Perform Add
-------------------------------------------------------------------------------------------*/

BEGIN
 BEGIN TRANSACTION
	IF @LIC_PN_ID IS NULL OR @LIC_PN_ID = 0 
	-- If the premise is new then it goes here
	   BEGIN      
		  INSERT INTO PremisesNames 
					 (PN_Name,PN_Status,LastUpdateDateTime,	LastUpdateUser,CreationDateTime,CreationUser)
			  VALUES (@PN_Name,'C',GETDATE(),@LastUpdateUser,GETDATE(),@LastUpdateUser)
		  SELECT @Error = @@Error 
		  IF @Error <> 0 
			 GOTO Add_End
		  SELECT @LIC_PN_ID = SCOPE_IDENTITY(), @Premise_Update = 1
	   END      

-- If a change of class for a related licence then a new licence 
-- number must be created 
	IF @CreateLicence = 'R' AND (@APP_LC_ID <> 0 AND @APP_LC_ID IS NOT NULL)
	   BEGIN
		  SELECT @New_Class = SUBSTRING(ISNULL(LN_LicenceNumber,'   '),1,3) FROM LicenceNumbers WHERE LN_LIC_ID = @APP_LIC_ID
		  SELECT @Check_Class = SUBSTRING(ISNULL(CONVERT(CHAR(7),LC_Number),'   '),1,3) FROM LicenceClasses WHERE LC_ID = @APP_LC_ID
		  IF @New_Class <> @Check_Class 
			 SELECT @Class_Change = 1
	   END

-- Create a licence record for 'C' types
	IF @CreateLicence = 'C'
	   BEGIN
		  INSERT INTO Licences 
					 (LIC_PN_ID, LIC_PremisesAddress1, LIC_PremisesAddress2, LIC_PremisesTown, LIC_PremisesPostcode,
					  LIC_PremisesPhone, LIC_PremisesFax, LIC_PremisesEmail, LIC_PremisesWeb, LIC_PremisesState,
					  LIC_TripCat, LastUpdateDateTime,	LastUpdateUser, CreationDateTime, CreationUser, LIC_InterstateLicenceNumber, LIC_InterstateJursidiction, LIC_PremisesMobile)
				VALUES
					 (@LIC_PN_ID, @LIC_PremisesAddress1, @LIC_PremisesAddress2, @LIC_PremisesTown, @LIC_PremisesPostcode,
					  @LIC_PremisesPhone, @LIC_PremisesFax, @LIC_PremisesEmail, @LIC_PremisesWeb, @LIC_PremisesState, 
					  '1', GETDATE(), @LastUpdateUser, GETDATE(), @LastUpdateUser, @LIC_InterstateLicenceNumber, @LIC_InterstateJursidiction, @LIC_PremisesMobile)               

		  SELECT @Error = @@Error 
		  IF @Error <> 0 
			 GOTO Add_End

		  SELECT @Temp_APP_LIC_ID = SCOPE_IDENTITY()
		  
		  --Add the Licence History record
			insert LicencePremisesAddressHistory (LPAH_LIC_ID, LPAH_PremisesAddress1, LPAH_PremisesAddress2,
				LPAH_PremisesTown, LPAH_PremisesPostCode, LPAH_PremisesState, LPAH_PremisesDateFrom,
				LastUpdateDateTime,	LastUpdateUser,CreationDateTime,CreationUser)
			values(@Temp_APP_LIC_ID, @LIC_PremisesAddress1, @LIC_PremisesAddress2, 
				@LIC_PremisesTown, @LIC_PremisesPostcode, @LIC_PremisesState, getdate(),
				getdate(),	@LastUpdateUser, getdate(),	@LastUpdateUser)

-- If the premise table has been updated the update the LIC_ID
-- Only happens for new licence records
		  IF @Premise_Update = 1 
			 UPDATE PremisesNames SET PN_LIC_ID = @Temp_APP_LIC_ID WHERE PN_ID = @LIC_PN_ID

		  SELECT @Error = @@Error 
		  IF @Error <> 0 
			 GOTO Add_End
	   END     

	IF (@CreateLicence = 'R' AND @Class_Change = 1) OR @CreateLicence = 'C'
	   BEGIN
-- Create a new licence number for related records with a class 
-- change using the @APP_LIC_ID passed in
		  IF (@CreateLicence = 'R' AND @Class_Change = 1)
			 SELECT @LN_LIC_ID = @APP_LIC_ID
-- Create a Licence Number record for new licences using the 
-- just created @Temp_APP_LIC_ID            
		  ELSE
			 SELECT @LN_LIC_ID = @Temp_APP_LIC_ID

		  EXEC spCreateLicenceNumber @APP_LC_ID, @NewLicenceNumber=@LN_LicenceNumber OUTPUT
		  INSERT INTO LicenceNumbers 
					 (LN_LIC_ID, LN_LicenceNumber, LastUpdateDateTime, LastUpdateUser, CreationDateTime, CreationUser, LN_LC_ID)
			  VALUES
					 (@LN_LIC_ID, @LN_LicenceNumber, GETDATE(), @LastUpdateUser, GETDATE(), @LastUpdateUser, dbo.udfGetLicenceClass(@LN_LicenceNumber)) 

		  SELECT @Error = @@Error 
		  IF @Error <> 0 
			 GOTO Add_End
-- APP_LN_ID is set to the new Licence Number ID as the LIC_LN_ID 
		  SELECT @APP_LN_ID = SCOPE_IDENTITY()

		  IF @Premise_Update = 1 
			 UPDATE Licences SET LIC_LN_ID = @APP_LN_ID WHERE LIC_ID = @Temp_APP_LIC_ID
	   END

-- If there is no Licence Details record for the stream then create one    
	IF @CreateLicence = 'R' AND NOT EXISTS (SELECT LD_ID FROM LicenceDetails WHERE LD_LIC_ID = @APP_LIC_ID AND LD_AS_ID = @AS_ID )
	   BEGIN
		  
		  INSERT INTO LicenceDetails
					  (LD_LIC_ID, LD_Status, LD_AS_ID, LastUpdateDateTime,	LastUpdateUser, CreationDateTime, CreationUser)
				   VALUES
					  (@APP_LIC_ID, 'A', @AS_ID, GETDATE(), @LastUpdateUser, GETDATE(), @LastUpdateUser) 
		  
		  SET @LS_LD_ID=SCOPE_IDENTITY()
	
		  INSERT INTO LicenceStatus (LS_LD_ID,LS_Status,LS_DateFrom, 
				LastUpdateDateTime,	LastUpdateUser,CreationDateTime,CreationUser)
		  VALUES(@LS_LD_ID,'A',dbo.udfGetDateOnly(GETDATE()),
				GETDATE(), @LastUpdateUser, GETDATE(), @LastUpdateUser)
				
		  SELECT @Error = @@Error 
		  IF @Error <> 0 
			 GOTO Add_End
	   END    

-- Create one regardless as the licence record is new   
	IF @CreateLicence = 'C' 
	   BEGIN
		  INSERT INTO LicenceDetails
					  (LD_LIC_ID, LD_Status, LD_AS_ID, LastUpdateDateTime,	LastUpdateUser, CreationDateTime, CreationUser)
				   VALUES
					  (@Temp_APP_LIC_ID, 'A', @AS_ID, GETDATE(), @LastUpdateUser, GETDATE(), @LastUpdateUser) 
	
		  SET @LS_LD_ID=SCOPE_IDENTITY()
	
		  INSERT INTO LicenceStatus (LS_LD_ID,LS_Status,LS_DateFrom, 
				LastUpdateDateTime,	LastUpdateUser,CreationDateTime,CreationUser)
		  VALUES(@LS_LD_ID,'A',dbo.udfGetDateOnly(GETDATE()),
				GETDATE(), @LastUpdateUser, GETDATE(), @LastUpdateUser)
				
		  SELECT @Error = @@Error 
		  IF @Error <> 0 
			 GOTO Add_End
	   END   
		 
	-- If creating then set the LIC_ID   
	IF @CreateLicence = 'C' 
	   SELECT @APP_LIC_ID = @Temp_APP_LIC_ID


	--to get LIC_ID if iApply form creates new licence number only [achira]
	IF @CreateLicenceOnly = 1
	   	BEGIN
			SELECT @APP_LC_Desc = LC_Desc FROM LicenceClasses WHERE LC_ID = @APP_LC_ID --achira
			GOTO Add_End 
		END
	
	-- Create a new application number CR290 - only if @APP_ApplicNumber is not entered                               
	if isnull(convert(numeric(18,0),@APP_ApplicNumber),0)=0
		EXEC spCreateApplicationNumber @AS_ID, @NewApplicationNumber=@APP_ApplicNumber OUTPUT

-- Get the application Stream Type from the application type
	SELECT @APP_AST_ID = AST_ID FROM ApplicationStreamTypes WHERE AST_AT_ID = @AT_ID AND AST_AS_ID = @AS_ID

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
		APP_AdvInstructIssued, APP_OutstandingDocRec, APP_OutstandingDocRequested)
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
		LEFT(@HearingType,1), LEFT(@HearingAuthority,1), GETDATE(),	@LastUpdateUser, GETDATE(), @LastUpdateUser, null, @APP_Exemption,
		@APP_AdvInstructIssued, @APP_OutstandingDocRec, @APP_OutstandingDocRequested) 

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

--Create the allocation record
	--IF @Allocated_AU_ID IS NOT NULL
	BEGIN
		INSERT INTO FileAllocation
			(FA_AS_ID,
			FA_LC_ID,
			FA_OBJT_ID, 
			FA_OBJ_ID,
			FA_AU_ID_AllocTo, 
			FA_AU_ID_AllocBy,
			FA_AllocatedDate,
			FA_CB_ID_Status,
			LastUpdateDateTime,
			LastUpdateUser,
			CreationDateTime,
			CreationUser)
		VALUES
			(@AS_ID,
			@APP_LC_ID,
			(SELECT OBJT_ID FROM ObjectType WHERE OBJT_Code = 'APP'),
			@id_col,
			@FA_AU_ID_AllocTo,
			(SELECT AU_ID FROM AppUsers WHERE AU_Name = @LastUpdateUser),
			GETDATE(),
			(SELECT CB_ID FROM ComboBoxes WHERE CB_Code = 'FAA'),
			GETDATE(),
			@LastUpdateUser,
			GETDATE(),
			@LastUpdateUser)
	END

--Update the attachments to point to the application
	IF @OA_ID IS NOT NULL
	BEGIN
		UPDATE documents 
		SET DOC_OBJ_ID = @id_col, DOC_OBJT_ID = (SELECT TOP 1 OBJT_ID FROM ObjectType WHERE OBJT_Code = 'LAM') 
		WHERE DOC_OBJ_ID = @OA_ID and DOC_OBJT_ID = (SELECT TOP 1 OBJT_ID FROM ObjectType WHERE OBJT_Code = 'ONLINE')
	END

	
-- All done (Ta-dah)
Add_End:       
 IF @Error = 0 
  BEGIN
	COMMIT TRANSACTION
	--@APP_LC_Desc as LC_Desc added by achira
	SELECT @APP_LIC_ID as LIC_ID, @APP_LN_ID as LN_ID, @APP_LC_ID as LC_ID, @LIC_PN_ID as PN_ID, @LN_LicenceNumber as LN_LicenceNumber, @PN_Name as PN_Name,@APP_LC_Desc as LC_Desc,@id_col as APP_ID 
  END
 ELSE
	ROLLBACK TRANSACTION
END


 

