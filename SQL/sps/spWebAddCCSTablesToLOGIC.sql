USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebAddCCSTablesToLOGIC]    Script Date: 14/07/2022 12:15:09 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		Achira Warnakulasuriya
-- Create date: 08/02/2021
-- Description:	Adding Information to CCS_Complaint, CCS_Consumer, CCS_ComplaintProductPractice tables in LOGIC
-- =============================================
ALTER PROCEDURE [dbo].[spWebAddCCSTablesToLOGIC]
	@DateReceived DATETIME = NULL,
	@Description VARCHAR(MAX) = NULL,
	@IsOpen BIT,
	@CB_ID_CCS_ComplaintType NUMERIC (18,0) = NULL,
	@CB_ID_CCS_Source NUMERIC (18,0) = NULL,
	@CB_ID_CCS_FileLocation NUMERIC (18,0) = NULL,
	@TraderContactName VARCHAR(100) = NULL,
	@TraderBusinessName VARCHAR(100) = NULL ,
	@TraderMobile VARCHAR(20) = NULL,
	@TraderEMail VARCHAR(100) = NULL,
	@CB_ID_CCS_LicenceRequirement NUMERIC (18,0) = NULL,
	@LastUpdateUser VARCHAR(100), 
	@AppUserID INT = NULL,
	@ComplaintID INT = NULL,

	--CCS_Consumer Table parameters
	@FullName		VARCHAR(100) = NULL,
	@Mobile			VARCHAR(15) = NULL,
	@EMail			VARCHAR(100) = NULL,
	@StreetName		VARCHAR(30) = NULL,
	@Suburb			VARCHAR(30) = NULL,
	@Postcode		VARCHAR(6) = NULL,
	@CB_ID_Gender	NUMERIC(18,0) = NULL,
	@Phone			VARCHAR(50) = NULL,
	@IsPOBox		BIT,
	@PODetails		VARCHAR(50) = NULL,
	@LevelNo		VARCHAR(10) = NULL,
	@UnitNo			VARCHAR(10) = NULL,
	@StreetNo		VARCHAR(20) = NULL,
	@StreetType		VARCHAR(50) = NULL,
	@State			VARCHAR(20) = NULL,
	@Country		VARCHAR(100) = NULL,
	@IsOverSeasOrOdd BIT,
	@Line1			VARCHAR(45) = NULL,
	@Line2			VARCHAR(45) = NULL,
	@Line3			VARCHAR(45) = NULL,
	@Line4			VARCHAR(45) = NULL,
	@IFRInvID		VARCHAR(45) = NULL,

	--CCS_ComplaintProductPractice Table Parameters
	@ProductID NUMERIC (18,0) = NULL,
	@PracticeID NUMERIC (18,0) = NULL,
	@ISPrimary BIT = NULL,
			
	@Switch VARCHAR(100)
AS
BEGIN
	
	SET NOCOUNT ON;
	DECLARE 
		@Complaint_ID NUMERIC(18,0),
		@Consumer_ID NUMERIC(18,0),
		@CCS_ComplaintProductPractice_ID NUMERIC(18,0),
		@id_col	NUMERIC(18,0)
		

	IF @Switch = 'CCS_Complaint' BEGIN

		-- GENERATE FileNo
		DECLARE @FileNo AS VARCHAR(10)
		SET @FileNo = (SELECT (CAST((SELECT TOP 1 LOO_Desc FROM Lookups WHERE LOO_Code = 'COMPFileNo')+1 AS VARCHAR) + '/' + (CAST (YEAR(GETDATE()) % 100 AS VARCHAR))))
		IF LEN(@FileNo)<8 BEGIN 
			SET @FileNo = RIGHT('000'+@FileNo,7) 
		END

		-- ALLOCATE FILE 
		SELECT @AppUserID = AU_ID  FROM CCS_AppUserRegion c
		INNER JOIN AppUsers au ON au.AU_ID = c.ID
		INNER JOIN AppUserGroups aug ON aug.AUG_AU_ID = au.AU_ID
		INNER JOIN AppGroup ag ON ag.apg_id = aug.AUG_APG_ID
		INNER JOIN ComboBoxes cb ON cb.CB_ID = c.CB_ID_CCS_Region
		WHERE CB_ID_CCS_Region = 1398 AND au.AU_ID = 1466 

		-- INSERT THE COMPLAINT
		INSERT INTO CCS_Complaint (FileNo, DateReceived, Description, IsOpen, CB_ID_CCS_ComplaintType, CB_ID_CCS_Source, CB_ID_CCS_FileLocation, TraderContactName, TraderBusinessName, TraderMobile, TraderEMail, CB_ID_CCS_LicenceRequirement, LastUpdateDateTime, CreationDateTime, LastUpdateUser, AppUserID)
		VALUES (@FileNo, @DateReceived, @Description, @IsOpen, @CB_ID_CCS_ComplaintType, @CB_ID_CCS_Source, @CB_ID_CCS_FileLocation, @TraderContactName, @TraderBusinessName, @TraderMobile, @TraderEMail, @CB_ID_CCS_LicenceRequirement, GETDATE(), GETDATE(), @LastUpdateUser, @AppUserID)

		-- UPDATE THE FileNo COUNTER
		UPDATE Lookups SET LOO_Desc = LOO_Desc+1 WHERE LOO_Code = 'COMPFileNo'
	
		SELECT @Complaint_ID = SCOPE_IDENTITY()
		
		SELECT @Complaint_ID AS Complaint_ID, @FileNo AS FileNo		
	END

	IF @Switch = 'CCS_Consumer' BEGIN
		
		INSERT INTO CCS_Consumer(FullName, Mobile, EMail, LastUpdateUser, LastUpdateDateTime, CreationDateTime, ComplaintID, StreetName, Suburb, Postcode, CB_ID_Gender, Phone, IsPOBox, PODetails, LevelNo, UnitNo, StreetNo, StreetType, State, Country, IsOverSeasOrOdd, Line1, Line2, Line3, Line4, InvestigationID)
		VALUES (@FullName, @Mobile, @EMail, @LastUpdateUser, GETDATE(), GETDATE(), @ComplaintID, @StreetName, @Suburb, @Postcode, @CB_ID_Gender, @Phone, @IsPOBox, @PODetails, @LevelNo, @UnitNo, @StreetNo, @StreetType, @State, @Country, @IsOverSeasOrOdd, @Line1, @Line2, @Line3, @Line4, @IFRInvID)

		SELECT @Consumer_ID = SCOPE_IDENTITY()

		--update CCS_Complaint table
		UPDATE CCS_Complaint
			SET ConsumerID = @Consumer_ID
		WHERE ID = @ComplaintID
					
		SELECT @Consumer_ID AS Consumer_ID, @ComplaintID AS Complaint_ID	
	END

	IF @Switch = 'CCS_ComplaintProductPractice' BEGIN

		INSERT INTO CCS_ComplaintProductPractice(ComplaintID, ProductID, PracticeID, ISPrimary)
		VALUES (@ComplaintID, @ProductID, @PracticeID, @ISPrimary)

		SELECT @CCS_ComplaintProductPractice_ID = SCOPE_IDENTITY()
			
		SELECT @CCS_ComplaintProductPractice_ID as CCS_ComplaintProductPractice_ID, @ComplaintID as Complaint_ID	
	END
   
END