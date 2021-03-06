USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebAddIFRInvestigation]    Script Date: 11/07/2022 4:06:31 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Achira Warnakulasuriya>
-- Create date: <8/07/2022>
-- Description:	<Insert data to LOGIC IFR_Investigation table>
-- =============================================
ALTER PROCEDURE [dbo].[spWebAddIFRInvestigation]
	-- Add the parameters for the stored procedure here
	@RelatedFile				VARCHAR(50) = NULL,
	@EntityID					NUMERIC(18,0) = NULL,
	@LicenceID					NUMERIC(18,0) = NULL,
	@EntityTypeID				NUMERIC(18,0) = NULL,
	@EntityContactName			VARCHAR(50) = NULL,
	@EntityBusinessName			VARCHAR(150) = NULL,
	@EntityPhone				VARCHAR(20) = NULL,
	@EntityMobile				VARCHAR(20) = NULL,
	@EntityBusinessPhone		VARCHAR(20) = NULL,
	@EntityEMail				VARCHAR(50) = NULL,
	@EntityWeb					VARCHAR(50) = NULL,
	@EntityABN					VARCHAR(40) = NULL,
	@DateReceived				DATETIME = NULL,
	@CB_ID_IFR_InvestigationStatus	NUMERIC(18,2) = NULL,
	@ActID						NUMERIC(18,0) = NULL,
	@EntityLicenseeName			VARCHAR(100) = NULL,
	@CB_ID_LicencingArea		Numeric(18,0) = NULL,
	@LicenceReferenceNumber		VARCHAR(15) = NULL,
	@PremisesName				VARCHAR(100) = NULL,
	@EntityARBN					VARCHAR(20) = NULL,
	@SupportAppUserID			NUMERIC(18,0) = NULL,
	@AppUserID					NUMERIC(18,0) = NULL,
	@CB_ID_ReceivedFrom			NUMERIC(18,0) = NULL,
	@CB_ID_IFR_LicenceRequirement NUMERIC(18,0),
	@CB_ID_LegalStatus			NUMERIC(18,0),
	@LastUpdateUser		   		VARCHAR(50),
	@Switch						VARCHAR(100)
AS
BEGIN
	SET NOCOUNT ON;

    IF @Switch = 'Insert' BEGIN
		-- GENERATE FileNo
		DECLARE @FileNo as varchar(10)
		SET @FileNo = (select (cast((select top 1 LOO_Desc from Lookups where LOO_Code = 'INVFileNo')+1 as varchar) + '/' + (cast (YEAR(GETDATE()) % 100 as varchar))))
		IF LEN(@FileNo)<8 BEGIN SET @FileNo = RIGHT('000'+@FileNo,7) END
			
		-- UPDATE THE FileNo COUNTER
		UPDATE Lookups SET LOO_Desc = LOO_Desc+1 WHERE LOO_Code = 'INVFileNo'
		
		--inserting data 
		INSERT INTO IFR_Investigation (FileNo, RelatedFile, EntityID, LicenceID, EntityTypeID, EntityContactName, EntityBusinessName, EntityPhone, EntityMobile, EntityBusinessPhone, EntityEMail, EntityWeb, EntityABN, DateReceived, CB_ID_IFR_InvestigationStatus, ActID, EntityLicenseeName, CB_ID_LicencingArea, LicenceReferenceNumber, PremisesName, EntityARBN, SupportAppUserID, AppUserID, CB_ID_ReceivedFrom, LastUpdateUser, LastUpdateDateTime, CreationUser, CreationDateTime, CB_ID_IFR_LicenceRequirement, CB_ID_LegalStatus)
		VALUES(@FileNo, @RelatedFile, @EntityID, @LicenceID, @EntityTypeID, @EntityContactName, @EntityBusinessName, @EntityPhone, @EntityMobile, @EntityBusinessPhone, @EntityEMail, @EntityWeb, @EntityABN, @DateReceived, @CB_ID_IFR_InvestigationStatus, @ActID, @EntityLicenseeName, @CB_ID_LicencingArea, @LicenceReferenceNumber, @PremisesName, @EntityARBN, @SupportAppUserID, @AppUserID, @CB_ID_ReceivedFrom, @LastUpdateUser, GETDATE(), @LastUpdateUser, GETDATE(), @CB_ID_IFR_LicenceRequirement, @CB_ID_LegalStatus)
	
		SELECT SCOPE_IDENTITY() AS ID, @FileNo AS 'File_No'
	END
END
